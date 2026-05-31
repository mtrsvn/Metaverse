<?php

namespace App\Http\Controllers;

use App\Support\ProductPreorder;
use App\Services\AuditLogger;
use App\Support\ProductSizeInventory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function handle(Request $request)
    {
        ProductSizeInventory::ensureSchema();
        ProductPreorder::ensureSchema();

        if ($request->isMethod('post')) {
            if ($request->has('add_to_cart')) {
                return $this->addToCart($request);
            }
            if ($request->has('remove_item')) {
                return $this->removeItem($request);
            }
            if ($request->has('update_quantity')) {
                return $this->updateQuantity($request);
            }
            if ($request->has('checkout')) {
                return $this->checkout($request);
            }
        }

        return $this->showCart($request);
    }

    private function addToCart(Request $request)
    {
        $userId = (int) session('user_id');
        $productId = (int) $request->input('product_id', 0);
        $productSize = ProductSizeInventory::normalizeSize((string) $request->input('product_size', ''));
        $quantity = (int) $request->input('quantity', 1);
        $productName = (string) $request->input('product_name', 'Unknown Product');
        $productPrice = (float) $request->input('product_price', 0);
        $productImage = (string) $request->input('product_image', '');

        if ($productId <= 0 || $userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Unable to add the product to your cart.'], 422);
        }

        if ($productSize === null) {
            return response()->json(['success' => false, 'message' => 'Please choose a size before adding to cart.'], 422);
        }

        if ($quantity < 1) {
            $quantity = 1;
        }

        $product = DB::table('products')
            ->where('id', $productId)
            ->first(['stock', 'size_stock', 'is_preorder', 'preorder_note']);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $productData = ProductPreorder::decorateProductRecord((array) $product);
        $sizeStock = ProductSizeInventory::parseSizeStock($product->size_stock ?? null, (int) ($product->stock ?? 0));
        $stock = ProductSizeInventory::stockForSize($sizeStock, $productSize);
        $maxCartQty = 99;

        if ($stock <= 0 && ! ProductPreorder::shouldTreatAsPreorder($productData, $quantity, $stock)) {
            return response()->json(['success' => false, 'message' => ProductSizeInventory::label($productSize) . ' is out of stock.'], 422);
        }

        $currentQty = (int) $this->cartItemQuery($userId, $productId, $productSize)->value('quantity');
        $totalQty = $currentQty + $quantity;
        if ($totalQty > $maxCartQty) {
            $quantity = $maxCartQty - $currentQty;
            if ($quantity <= 0) {
                return response()->json(['success' => false, 'message' => 'Cart quantity limit reached for this size.'], 422);
            }
            $totalQty = $currentQty + $quantity;
        }

        $isPreorder = ProductPreorder::shouldTreatAsPreorder($productData, $totalQty, $stock);
        if (! $isPreorder && $totalQty > $stock) {
            $quantity = $stock - $currentQty;
            if ($quantity <= 0) {
                return response()->json(['success' => false, 'message' => 'You already have the maximum stock for this size in your cart.'], 422);
            }
            $totalQty = $currentQty + $quantity;
        }

        $updatedQty = $currentQty + $quantity;
        if ($currentQty > 0) {
            $this->cartItemQuery($userId, $productId, $productSize)->update([
                'quantity' => $updatedQty,
                'product_name' => $productName,
                'product_price' => $productPrice,
                'product_image' => $productImage,
                'product_size' => $productSize,
            ]);
        } else {
            DB::table('cart')->insert([
                'user_id' => $userId,
                'product_id' => $productId,
                'product_name' => $productName,
                'product_price' => $productPrice,
                'product_image' => $productImage,
                'product_size' => $productSize,
                'quantity' => $quantity,
            ]);
        }

        $safeName = is_string($productName) ? $productName : 'Unknown Product';
        $sizeLabel = ProductSizeInventory::label($productSize);
        $auditAction = $isPreorder ? 'Added to cart as pre-order' : 'Added to cart';
        AuditLogger::logAction($userId, sprintf('%s: %s - %s x%d', $auditAction, $safeName, $sizeLabel, $quantity));

        return response()->json([
            'success' => true,
            'message' => $isPreorder
                ? sprintf('%s (%s) added to cart as pre-order.', $safeName, $sizeLabel)
                : sprintf('%s (%s) added to cart.', $safeName, $sizeLabel),
            'quantity' => $updatedQty,
            'is_preorder' => $isPreorder,
        ]);
    }

    private function removeItem(Request $request)
    {
        $userId = (int) session('user_id');
        $productId = (int) $request->input('product_id', 0);
        $productSize = ProductSizeInventory::normalizeSize((string) $request->input('product_size', 'medium')) ?? 'medium';

        if ($userId > 0) {
            $existing = $this->cartItemQuery($userId, $productId, $productSize)
                ->first(['product_name', 'quantity']);

            $this->cartItemQuery($userId, $productId, $productSize)->delete();

            if ($existing) {
                $safeName = is_string($existing->product_name ?? null) ? $existing->product_name : 'Unknown Product';
                $qty = (int) ($existing->quantity ?? 0);
                $sizeLabel = ProductSizeInventory::label($productSize);
                AuditLogger::logAction($userId, sprintf('Removed from cart: %s - %s x%d', $safeName, $sizeLabel, $qty));
            }
        }

        return redirect(route('cart', [], false));
    }

    private function updateQuantity(Request $request)
    {
        $userId = (int) session('user_id');
        $productId = (int) $request->input('product_id', 0);
        $productSize = ProductSizeInventory::normalizeSize((string) $request->input('product_size', 'medium')) ?? 'medium';
        $newQuantity = (int) $request->input('quantity', 1);

        if ($userId > 0 && $newQuantity > 0) {
            $product = DB::table('products')
                ->where('id', $productId)
                ->first(['stock', 'size_stock', 'is_preorder', 'preorder_note']);

            if (! $product) {
                $this->cartItemQuery($userId, $productId, $productSize)->delete();

                return redirect(route('cart', ['removed' => 'out_of_stock'], false));
            }

            $productData = ProductPreorder::decorateProductRecord((array) $product);
            $sizeStock = ProductSizeInventory::parseSizeStock($product->size_stock ?? null, (int) ($product->stock ?? 0));
            $stock = ProductSizeInventory::stockForSize($sizeStock, $productSize);
            $isPreorder = ProductPreorder::shouldTreatAsPreorder($productData, $newQuantity, $stock);

            if ($stock <= 0 && ! $isPreorder) {
                $this->cartItemQuery($userId, $productId, $productSize)->delete();

                return redirect(route('cart', ['removed' => 'out_of_stock'], false));
            }

            if ($newQuantity > 99) {
                $newQuantity = 99;
            }

            if (! $isPreorder && $newQuantity > $stock) {
                $newQuantity = $stock;
            }

            $existing = $this->cartItemQuery($userId, $productId, $productSize)
                ->first(['product_name', 'quantity']);

            $oldQty = $existing ? (int) $existing->quantity : null;
            $safeName = $existing && is_string($existing->product_name) ? $existing->product_name : 'Unknown Product';
            $sizeLabel = ProductSizeInventory::label($productSize);

            $this->cartItemQuery($userId, $productId, $productSize)
                ->update(['quantity' => $newQuantity]);

            if ($existing) {
                $delta = $newQuantity - (int) $oldQty;
                $sign = $delta > 0 ? '+' : ($delta < 0 ? '-' : '');
                $deltaAbs = abs($delta);
                $message = $sign !== ''
                    ? sprintf('Updated cart qty: %s - %s %d → %d (%s%d)', $safeName, $sizeLabel, $oldQty, $newQuantity, $sign, $deltaAbs)
                    : sprintf('Updated cart qty: %s - %s unchanged at %d', $safeName, $sizeLabel, $newQuantity);
                if ($isPreorder) {
                    $message .= ' [pre-order]';
                }
                AuditLogger::logAction($userId, $message);
            }
        }

        return redirect(route('cart', [], false));
    }

    private function checkout(Request $request)
    {
        $userId = (int) session('user_id');

        if ($userId <= 0) {
            return redirect(route('cart', [], false));
        }

        DB::beginTransaction();

        $items = DB::table('cart')
            ->where('user_id', $userId)
            ->get(['product_id', 'product_name', 'product_price', 'product_size', 'quantity']);

        $failed = false;
        $outOfStock = [];
        $hasPreorders = false;

        foreach ($items as $item) {
            $qty = (int) $item->quantity;
            $pid = (int) $item->product_id;
            $size = ProductSizeInventory::normalizeSize((string) ($item->product_size ?? '')) ?? 'medium';

            $product = DB::table('products')
                ->where('id', $pid)
                ->lockForUpdate()
                ->first(['id', 'stock', 'size_stock', 'is_preorder', 'preorder_note']);

            if (! $product) {
                $outOfStock[] = $pid;
                $failed = true;
                break;
            }

            $productData = ProductPreorder::decorateProductRecord((array) $product);
            $sizeStock = ProductSizeInventory::parseSizeStock($product->size_stock ?? null, (int) ($product->stock ?? 0));
            $stock = ProductSizeInventory::stockForSize($sizeStock, $size);
            $isPreorder = ProductPreorder::shouldTreatAsPreorder($productData, $qty, $stock);

            if (! $isPreorder) {
                $reservedValues = ProductSizeInventory::adjustStockValues($product, $size, -$qty);
                if ($reservedValues === null) {
                    $outOfStock[] = $pid;
                    $failed = true;
                    break;
                }

                $affected = DB::table('products')->where('id', $pid)->update($reservedValues);
                if ($affected === 0) {
                    $outOfStock[] = $pid;
                    $failed = true;
                    break;
                }
            }

            $inserted = DB::table('purchases')->insert([
                'user_id' => $userId,
                'product_id' => $pid,
                'product_size' => $size,
                'quantity' => $qty,
                'product_name' => $item->product_name ?? 'Unknown Product',
                'product_price' => $item->product_price ?? 0,
                'is_preorder' => $isPreorder ? 1 : 0,
                'preorder_note' => $isPreorder ? ProductPreorder::noteOrNull($productData['preorder_note'] ?? '') : null,
            ]);

            if (! $inserted) {
                $failed = true;
                break;
            }

            $hasPreorders = $hasPreorders || $isPreorder;
        }

        if ($failed || !empty($outOfStock)) {
            DB::rollBack();
            return redirect(route('cart', ['error' => 'some_out_of_stock'], false));
        }

        DB::commit();

        DB::table('cart')->where('user_id', $userId)->delete();

        AuditLogger::logAction(
            $userId,
            $hasPreorders
                ? 'Purchase created, including pre-order items, and awaiting staff approval'
                : 'Purchase created, stock reserved and awaiting staff approval'
        );

        session()->put('checkout_success', true);

        return redirect(route('cart', [], false));
    }

    private function showCart(Request $request)
    {
        $userId = (int) session('user_id');

        $cartItems = [];
        $total = 0.0;
        $hasOutOfStock = false;

        if ($userId > 0) {
            $rows = DB::table('cart')
                ->where('user_id', $userId)
                ->get(['product_id', 'product_name', 'product_price', 'product_image', 'product_size', 'quantity']);

            $productIds = $rows->pluck('product_id')->map(fn ($id) => (int) $id)->all();
            $stockMap = [];
            if (! empty($productIds)) {
                $stockMap = DB::table('products')
                    ->whereIn('id', $productIds)
                    ->get(['id', 'stock', 'size_stock', 'is_preorder', 'preorder_note'])
                    ->mapWithKeys(function ($product) {
                        $productData = ProductSizeInventory::decorateProductRecord((array) $product);
                        $productData = ProductPreorder::decorateProductRecord($productData);

                        return [(int) $product->id => $productData];
                    })
                    ->all();
            }

            foreach ($rows as $row) {
                $pid = (int) $row->product_id;
                $size = ProductSizeInventory::normalizeSize((string) ($row->product_size ?? '')) ?? 'medium';
                $product = $stockMap[$pid] ?? ProductPreorder::decorateProductRecord(ProductSizeInventory::decorateProductRecord(['stock' => 0]));
                $stock = ProductSizeInventory::stockForSize($product['size_stock_map'] ?? [], $size);
                $quantity = (int) $row->quantity;
                $isPreorder = ProductPreorder::shouldTreatAsPreorder($product, $quantity, $stock);
                $isOutOfStock = ! $isPreorder && $stock === 0;
                $cartItems[$pid . ':' . $size] = [
                    'product_id' => $pid,
                    'name' => $row->product_name,
                    'price' => (float) $row->product_price,
                    'image' => $row->product_image,
                    'size' => $size,
                    'size_label' => ProductSizeInventory::label($size),
                    'quantity' => $quantity,
                    'stock' => $stock,
                    'is_preorder' => $isPreorder,
                    'preorder_note' => $isPreorder ? ($product['preorder_note'] ?? '') : '',
                    'max_quantity' => $isPreorder ? 99 : max(1, $stock),
                    'is_out_of_stock' => $isOutOfStock,
                ];
            }
        }

        foreach ($cartItems as $item) {
            $subtotal = (float) $item['price'] * (int) $item['quantity'];
            $total += $subtotal;

            if (! empty($item['is_out_of_stock'])) {
                $hasOutOfStock = true;
            }
        }

        return view('cart.index', [
            'cartItems' => $cartItems,
            'total' => $total,
            'hasOutOfStock' => $hasOutOfStock,
        ]);
    }

    private function cartItemQuery(int $userId, int $productId, ?string $productSize): Builder
    {
        return DB::table('cart')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_size', $productSize ?? 'medium');
    }
}
