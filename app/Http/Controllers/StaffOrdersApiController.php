<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\LegacyMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffOrdersApiController extends Controller
{
    public function handle(Request $request)
    {
        if (! $this->canAccessOrders()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        $action = (string) ($request->query('action') ?? $request->input('action', ''));

        switch ($action) {
            case 'list':
                return $this->listOrders($request);
            case 'get':
                return $this->getOrder($request);
            case 'approve':
                return $this->approveOrder($request);
            case 'reject':
                return $this->rejectOrder($request);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid action']);
        }
    }

    private function listOrders(Request $request)
    {
        $status = (string) $request->query('status', 'all');

        if ($status === 'all') {
            $rows = DB::table('purchase_orders as po')
                ->leftJoin('users as u', 'po.user_id', '=', 'u.id')
                ->orderByDesc('po.created_at')
                ->get(['po.*', 'u.username', 'u.email as user_email']);
        } else {
            $rows = DB::table('purchase_orders as po')
                ->leftJoin('users as u', 'po.user_id', '=', 'u.id')
                ->where('po.status', $status)
                ->orderByDesc('po.created_at')
                ->get(['po.*', 'u.username', 'u.email as user_email']);
        }

        $orders = [];
        foreach ($rows as $row) {
            $items = json_decode($row->items ?? '[]', true);
            $row->item_count = is_array($items) ? count($items) : 0;
            $orders[] = $row;
        }

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    private function getOrder(Request $request)
    {
        $id = (int) $request->query('id', 0);

        $order = DB::table('purchase_orders as po')
            ->leftJoin('users as u', 'po.user_id', '=', 'u.id')
            ->where('po.id', $id)
            ->first(['po.*', 'u.username', 'u.email as user_email']);

        if ($order) {
            return response()->json(['success' => true, 'order' => $order]);
        }

        return response()->json(['success' => false, 'message' => 'Order not found']);
    }

    private function approveOrder(Request $request)
    {
        $orderId = (int) $request->input('order_id', 0);

        if ($orderId <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid order ID']);
        }

        $order = DB::table('purchase_orders as po')
            ->leftJoin('users as u', 'po.user_id', '=', 'u.id')
            ->where('po.id', $orderId)
            ->first(['po.*', 'u.username', 'u.email']);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found']);
        }

        if ($order->status === 'approved') {
            return response()->json(['success' => false, 'message' => 'Order is already approved']);
        }

        $updated = DB::table('purchase_orders')->where('id', $orderId)->update([
            'status' => 'approved',
            'approved_at' => DB::raw('NOW()'),
            'approved_by' => (int) session('user_id'),
        ]);

        if ($updated) {
            AuditLogger::logAction((int) session('user_id'), "Approved purchase order #{$orderId}");

            $customerEmail = $order->email ?? '';
            if (! empty($customerEmail)) {
                $customerName = $order->username ?? $customerEmail;
                $items = [];
                $decoded = json_decode($order->items ?? '[]', true);
                if (is_array($decoded)) {
                    foreach ($decoded as $it) {
                        $items[] = [
                            'name' => $it['name'] ?? ($it['title'] ?? 'Item'),
                            'quantity' => (int) ($it['quantity'] ?? 1),
                            'price' => (float) ($it['price'] ?? 0),
                        ];
                    }
                }
                $total = isset($order->total_amount) ? (float) $order->total_amount : 0.0;
                LegacyMailer::sendPurchaseConfirmation($customerEmail, $customerName, $items, $total);
            }

            return response()->json(['success' => true, 'message' => 'Order approved and customer notified']);
        }

        return response()->json(['success' => false, 'message' => 'Error approving order']);
    }

    private function rejectOrder(Request $request)
    {
        $orderId = (int) $request->input('order_id', 0);

        if ($orderId <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid order ID']);
        }

        $order = DB::table('purchase_orders as po')
            ->leftJoin('users as u', 'po.user_id', '=', 'u.id')
            ->where('po.id', $orderId)
            ->first(['po.*', 'u.username', 'u.email']);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found']);
        }

        if ($order->status === 'rejected') {
            return response()->json(['success' => false, 'message' => 'Order is already rejected']);
        }

        $updated = DB::table('purchase_orders')->where('id', $orderId)->update([
            'status' => 'rejected',
            'rejected_at' => DB::raw('NOW()'),
            'approved_by' => (int) session('user_id'),
        ]);

        if ($updated) {
            AuditLogger::logAction((int) session('user_id'), "Rejected purchase order #{$orderId}");

            $customerEmail = $order->email ?? '';
            if (! empty($customerEmail)) {
                $customerName = $order->username ?? $customerEmail;
                $items = [];
                $decoded = json_decode($order->items ?? '[]', true);
                if (is_array($decoded)) {
                    foreach ($decoded as $it) {
                        $items[] = [
                            'name' => $it['name'] ?? ($it['title'] ?? 'Item'),
                            'quantity' => (int) ($it['quantity'] ?? 1),
                            'price' => (float) ($it['price'] ?? 0),
                        ];
                    }
                }
                $total = isset($order->total_amount) ? (float) $order->total_amount : 0.0;
                $reason = trim((string) $request->input('reason', ''));
                LegacyMailer::sendPurchaseRejection($customerEmail, $customerName, $items, $total, $reason);
            }

            return response()->json(['success' => true, 'message' => 'Order rejected and customer notified']);
        }

        return response()->json(['success' => false, 'message' => 'Error rejecting order']);
    }

    private function canAccessOrders(): bool
    {
        return in_array(session('role'), ['staff_user', 'administrator'], true);
    }
}
