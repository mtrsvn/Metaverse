<?php

namespace App\Http\Controllers;

use App\Support\ProductPreorder;
use App\Services\AuditLogger;
use App\Services\LegacyMailer;
use App\Support\ProductSizeInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    public function approve(Request $request)
    {
        if (! $this->canApproveOrders()) {
            return view('empty');
        }

        ProductSizeInventory::ensureSchema();
    ProductPreorder::ensureSchema();

        if ($request->isMethod('post') && $request->has('approve_key')) {
            $uid = (int) $request->input('user_id', 0);
            $orderTime = (string) $request->input('order_time', '');
            if ($this->handleOrderAction($uid, $orderTime, 'approve')) {
                session()->put('admin_toast', [
                    'message' => 'Order approved and customer notified.',
                    'type' => 'success',
                ]);
            }
        }

        if ($request->isMethod('post') && $request->has('reject_key')) {
            $uid = (int) $request->input('user_id', 0);
            $orderTime = (string) $request->input('order_time', '');
            if ($this->handleOrderAction($uid, $orderTime, 'reject')) {
                session()->put('admin_toast', [
                    'message' => 'Order rejected and customer notified.',
                    'type' => 'warning',
                ]);
            }
        }

        if ($request->isMethod('post') && $request->has('bulk_action') && is_array($request->input('bulk_orders'))) {
            $action = (string) $request->input('bulk_action');
            $allowed = ['approve', 'reject'];
            if (in_array($action, $allowed, true)) {
                $handled = 0;
                foreach ((array) $request->input('bulk_orders') as $token) {
                    if (strpos($token, '|') === false) {
                        continue;
                    }
                    [$uidRaw, $timeRaw] = explode('|', $token, 2);
                    $uid = (int) $uidRaw;
                    $orderTime = (string) $timeRaw;
                    if ($this->handleOrderAction($uid, $orderTime, $action)) {
                        $handled++;
                    }
                }
                if ($handled > 0) {
                    session()->put('admin_toast', [
                        'message' => $action === 'approve' ? "{$handled} order(s) approved." : "{$handled} order(s) rejected.",
                        'type' => $action === 'approve' ? 'success' : 'warning',
                    ]);
                }
            }
        }

        $rows = DB::table('purchases as p')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->leftJoin('products as prod', 'p.product_id', '=', 'prod.id')
            ->select([
                'u.id as user_id',
                'u.username',
                'u.email',
                DB::raw("DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') AS order_time"),
                DB::raw("GROUP_CONCAT(CONCAT(COALESCE(prod.title, p.product_name), IF(p.product_size IS NOT NULL AND p.product_size <> '', CONCAT(' [', UPPER(p.product_size), ']'), ''), IF(COALESCE(p.is_preorder, 0) = 1, ' (Pre-order)', ''), ' x', p.quantity) ORDER BY p.id SEPARATOR ', ') AS item_list"),
                DB::raw('SUM(p.quantity) AS total_qty'),
                DB::raw('SUM(p.product_price * p.quantity) AS total_amount'),
            ])
            ->where('p.approved', 0)
            ->groupBy('u.id', 'u.username', 'u.email', 'order_time')
            ->orderByDesc('order_time')
            ->get();

        return view('staff.approve', [
            'rows' => $rows,
        ]);
    }

    public function productsManage()
    {
        if (! $this->canManageProducts()) {
            return view('empty');
        }

        return view('staff.products_manage');
    }

    private function handleOrderAction(int $uid, string $orderTime, string $action): bool
    {
        if ($uid <= 0 || $orderTime === '' || ! in_array($action, ['approve', 'reject'], true)) {
            return false;
        }

        $items = [];
        $total = 0.0;
        $email = null;
        $username = null;

        $detailRows = DB::table('purchases as p')
            ->join('users as u', 'p.user_id', '=', 'u.id')
            ->leftJoin('products as prod', 'p.product_id', '=', 'prod.id')
            ->where('p.user_id', $uid)
            ->where('p.approved', 0)
            ->whereRaw("DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') = ?", [$orderTime])
            ->get([
                'p.id',
                'p.user_id',
                'p.product_id',
                DB::raw('COALESCE(prod.title, p.product_name) AS product_name'),
                'p.product_price',
                'p.product_size',
                'p.quantity',
                'p.is_preorder',
                'u.email',
                'u.username',
            ]);

        foreach ($detailRows as $row) {
            $items[] = [
                'product_id' => (int) ($row->product_id ?? 0),
                'name' => $row->product_name ?? 'Unknown Product',
                'size' => ProductSizeInventory::normalizeSize((string) ($row->product_size ?? '')),
                'quantity' => (int) $row->quantity,
                'price' => (float) $row->product_price,
                'is_preorder' => (bool) ($row->is_preorder ?? false),
            ];
            $total += (float) $row->product_price * (int) $row->quantity;
            $email = $row->email ?? $email;
            $username = $row->username ?? $username;
        }

        if (empty($items)) {
            return false;
        }

        DB::beginTransaction();

        $statusValue = $action === 'approve' ? 1 : 2;
        $updated = DB::table('purchases')
            ->where('user_id', $uid)
            ->where('approved', 0)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') = ?", [$orderTime])
            ->update(['approved' => $statusValue]);

        if (! $updated) {
            DB::rollBack();
            return false;
        }

        if ($action === 'reject') {
            foreach ($items as $item) {
                $pid = (int) ($item['product_id'] ?? 0);
                $qty = (int) ($item['quantity'] ?? 0);
                if ($pid <= 0 || $qty <= 0 || ! empty($item['is_preorder'])) {
                    continue;
                }

                $product = DB::table('products')
                    ->where('id', $pid)
                    ->lockForUpdate()
                    ->first(['id', 'stock', 'size_stock']);

                if (! $product) {
                    DB::rollBack();
                    return false;
                }

                $restoredValues = ProductSizeInventory::adjustStockValues($product, (string) ($item['size'] ?? ''), $qty);
                if ($restoredValues === null) {
                    DB::rollBack();
                    return false;
                }

                $ok = DB::table('products')->where('id', $pid)->update($restoredValues);
                if ($ok === 0) {
                    DB::rollBack();
                    return false;
                }
            }
        }

        DB::commit();

        if (! empty($email)) {
            if ($action === 'approve') {
                LegacyMailer::sendPurchaseConfirmation($email, $username ?? $email, $items, $total);
            } else {
                LegacyMailer::sendPurchaseRejection($email, $username ?? $email, $items, $total);
            }
        }

        $msg = $action === 'approve'
            ? 'Order approved and customer notified'
            : 'Order rejected, stock restored and customer notified';
        AuditLogger::logAction($uid, $msg);

        return true;
    }

    private function canApproveOrders(): bool
    {
        return in_array(session('role'), ['staff_user', 'admin_sec'], true);
    }

    private function canManageProducts(): bool
    {
        return in_array(session('role'), ['staff_user', 'administrator', 'admin_sec'], true);
    }
}
