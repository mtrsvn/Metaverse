<?php

namespace App\Http\Controllers;

use App\Support\ProductPreorder;
use App\Services\AuditLogger;
use App\Support\ProductSizeInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StaffProductsApiController extends Controller
{
    public function handle(Request $request)
    {
        if (! $this->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $action = (string) ($request->query('action') ?? $request->input('action', ''));

        switch ($action) {
            case 'list':
                return $this->listProducts();
            case 'get':
                return $this->getProduct($request);
            case 'add':
                return $this->addProduct($request);
            case 'update':
                return $this->updateProduct($request);
            case 'delete':
                return $this->deleteProduct($request);
            case 'reorder':
                return $this->reorderProducts($request);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid action']);
        }
    }

    private function listProducts()
    {
        if (! $this->ensureColumns()) {
            return response()->json(['success' => false, 'message' => 'Database error']);
        }

        $rows = DB::table('products')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $products = [];
        foreach ($rows as $row) {
            $item = (array) $row;
            $item = ProductSizeInventory::decorateProductRecord($item);
            $item = ProductPreorder::decorateProductRecord($item);
            $discount = isset($item['discount']) ? (float) $item['discount'] : 0.0;
            $price = isset($item['price']) ? (float) $item['price'] : 0.0;
            $item['discounted_price'] = $discount > 0 ? round($price * (1 - $discount / 100), 2) : $price;
            $products[] = $item;
        }

        return response()->json(['success' => true, 'products' => $products]);
    }

    private function getProduct(Request $request)
    {
        if (! $this->ensureColumns()) {
            return response()->json(['success' => false, 'message' => 'Database error']);
        }

        $id = (int) $request->query('id', 0);
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid ID']);
        }

        $product = DB::table('products')->where('id', $id)->first();
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        $item = (array) $product;
        $item = ProductSizeInventory::decorateProductRecord($item);
        $item = ProductPreorder::decorateProductRecord($item);
        $discount = isset($item['discount']) ? (float) $item['discount'] : 0.0;
        $price = isset($item['price']) ? (float) $item['price'] : 0.0;
        $item['discounted_price'] = $discount > 0 ? round($price * (1 - $discount / 100), 2) : $price;

        return response()->json(['success' => true, 'product' => $item]);
    }

    private function addProduct(Request $request)
    {
        if (! $this->ensureColumns()) {
            return response()->json(['success' => false, 'message' => 'Database error']);
        }

        $title = trim((string) $request->input('title', ''));
        $price = (float) $request->input('price', 0);
        $category = trim((string) $request->input('category', ''));
        $description = trim((string) $request->input('description', ''));
        $image = trim((string) $request->input('image', ''));
        $discount = (float) $request->input('discount', 0);
        $sizeStock = $this->extractSizeStock($request);
        $stock = ProductSizeInventory::totalStock($sizeStock);
        $isPreorder = $request->boolean('is_preorder');
        $preorderNote = $isPreorder ? ProductPreorder::noteOrNull($request->input('preorder_note', '')) : null;

        if ($title === '' || $price <= 0) {
            return response()->json(['success' => false, 'message' => 'Title and price are required']);
        }

        $minOrder = (int) DB::table('products')->min('display_order');
        if ($minOrder === 0) {
            $minOrder = 1;
        }
        $nextOrder = $minOrder - 1;

        $id = DB::table('products')->insertGetId([
            'title' => $title,
            'price' => $price,
            'category' => $category,
            'description' => $description,
            'image' => $image,
            'display_order' => $nextOrder,
            'discount' => $discount,
            'stock' => $stock,
            'size_stock' => ProductSizeInventory::encodeSizeStock($sizeStock),
            'is_preorder' => $isPreorder ? 1 : 0,
            'preorder_note' => $preorderNote,
        ]);

        AuditLogger::logAction((int) session('user_id'), "Added product: {$title} (ID: {$id})");

        $product = ProductSizeInventory::decorateProductRecord([
            'id' => $id,
            'title' => $title,
            'price' => $price,
            'category' => $category,
            'description' => $description,
            'image' => $image,
            'discount' => $discount,
            'stock' => $stock,
            'size_stock' => ProductSizeInventory::encodeSizeStock($sizeStock),
            'is_preorder' => $isPreorder,
            'preorder_note' => $preorderNote,
        ]);
        $product = ProductPreorder::decorateProductRecord($product);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully',
            'product' => $product,
        ]);
    }

    private function updateProduct(Request $request)
    {
        $id = (int) $request->input('product_id', 0);
        $title = trim((string) $request->input('title', ''));
        $price = (float) $request->input('price', 0);
        $category = trim((string) $request->input('category', ''));
        $description = trim((string) $request->input('description', ''));
        $image = trim((string) $request->input('image', ''));
        $discount = (float) $request->input('discount', 0);
        $sizeStock = $this->extractSizeStock($request);
        $stock = ProductSizeInventory::totalStock($sizeStock);
        $isPreorder = $request->boolean('is_preorder');
        $preorderNote = $isPreorder ? ProductPreorder::noteOrNull($request->input('preorder_note', '')) : null;

        if ($id <= 0 || $title === '' || $price <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid input']);
        }

        $updated = DB::table('products')->where('id', $id)->update([
            'title' => $title,
            'price' => $price,
            'category' => $category,
            'description' => $description,
            'image' => $image,
            'discount' => $discount,
            'stock' => $stock,
            'size_stock' => ProductSizeInventory::encodeSizeStock($sizeStock),
            'is_preorder' => $isPreorder ? 1 : 0,
            'preorder_note' => $preorderNote,
        ]);

        if ($updated) {
            AuditLogger::logAction((int) session('user_id'), "Updated product ID {$id}: {$title}");

            $product = ProductSizeInventory::decorateProductRecord([
                'id' => $id,
                'title' => $title,
                'price' => $price,
                'category' => $category,
                'description' => $description,
                'image' => $image,
                'discount' => $discount,
                'stock' => $stock,
                'size_stock' => ProductSizeInventory::encodeSizeStock($sizeStock),
                'is_preorder' => $isPreorder,
                'preorder_note' => $preorderNote,
            ]);
            $product = ProductPreorder::decorateProductRecord($product);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to update product']);
    }

    private function deleteProduct(Request $request)
    {
        $id = (int) ($request->input('product_id') ?? $request->query('id', 0));
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid ID']);
        }

        $product = DB::table('products')->where('id', $id)->first(['title']);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        $deleted = DB::table('products')->where('id', $id)->delete();
        if ($deleted) {
            AuditLogger::logAction((int) session('user_id'), "Deleted product ID {$id}: {$product->title}");
            return response()->json(['success' => true, 'message' => 'Product deleted successfully']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to delete product']);
    }

    private function reorderProducts(Request $request)
    {
        if (! $this->ensureColumns()) {
            return response()->json(['success' => false, 'message' => 'Database error']);
        }

        $orderJson = (string) $request->input('order', '');
        $order = json_decode($orderJson, true);

        if (! is_array($order) || empty($order)) {
            return response()->json(['success' => false, 'message' => 'Invalid order data']);
        }

        DB::beginTransaction();
        try {
            foreach ($order as $index => $productId) {
                $displayOrder = $index + 1;
                DB::table('products')->where('id', (int) $productId)->update([
                    'display_order' => $displayOrder,
                ]);
            }
            DB::commit();
            AuditLogger::logAction((int) session('user_id'), 'Reordered products');
            return response()->json(['success' => true, 'message' => 'Products reordered successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to reorder products: ' . $e->getMessage()]);
        }
    }

    private function ensureColumns(): bool
    {
        if (! Schema::hasTable('products')) {
            return false;
        }

        if (! ProductSizeInventory::ensureSchema()) {
            return false;
        }
        if (! ProductPreorder::ensureSchema()) {
            return false;
        }

        if (! Schema::hasColumn('products', 'display_order')) {
            Schema::table('products', function ($table) {
                $table->integer('display_order')->default(0);
            });
            DB::statement('UPDATE products SET display_order = id WHERE display_order = 0 OR display_order IS NULL');
        }
        if (! Schema::hasColumn('products', 'discount')) {
            Schema::table('products', function ($table) {
                $table->float('discount')->default(0);
            });
        }
        if (! Schema::hasColumn('products', 'stock')) {
            Schema::table('products', function ($table) {
                $table->integer('stock')->default(0);
            });
        }

        return true;
    }

    private function extractSizeStock(Request $request): array
    {
        $input = (array) $request->input('size_stock', []);
        $sizeStock = [];

        foreach (ProductSizeInventory::sizeLabels() as $sizeKey => $label) {
            $sizeStock[$sizeKey] = max(0, (int) ($input[$sizeKey] ?? 0));
        }

        return $sizeStock;
    }

    private function isStaff(): bool
    {
        return in_array(session('role'), ['staff_user', 'administrator', 'admin_sec'], true);
    }
}
