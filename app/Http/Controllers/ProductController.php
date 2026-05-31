<?php

namespace App\Http\Controllers;

use App\Support\ProductPreorder;
use App\Support\ProductSizeInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        ProductSizeInventory::ensureSchema();
        ProductPreorder::ensureSchema();

        $query = DB::table('products');

        if (Schema::hasColumn('products', 'display_order')) {
            $query->orderBy('display_order')->orderBy('id');
        } else {
            $query->orderBy('id');
        }

        $products = $query->get()->map(function ($row) {
            $item = (array) $row;
            $item = ProductSizeInventory::decorateProductRecord($item);
            $item = ProductPreorder::decorateProductRecord($item);
            $discount = isset($item['discount']) ? (float) $item['discount'] : 0.0;
            $price = isset($item['price']) ? (float) $item['price'] : 0.0;
            $item['discounted_price'] = $discount > 0 ? round($price * (1 - $discount / 100), 2) : $price;

            // Normalize image path so relative values (e.g. "assets/cover.png") resolve to the public assets URL.
            $imagePath = isset($item['image']) ? (string) $item['image'] : '';
            $item['image'] = $this->resolveImageUrl($imagePath);

            return $item;
        })->toArray();

        $q = trim((string) $request->query('q', ''));
        $categoryFilter = trim((string) $request->query('category', ''));
        $sort = (string) $request->query('sort', '');

        $categories = [];
        foreach ($products as $product) {
            if (!empty($product['category'])) {
                $categories[] = $product['category'];
            }
        }
        $categories = array_values(array_unique($categories));
        sort($categories);

        if ($q !== '' || ($categoryFilter !== '' && $categoryFilter !== 'all')) {
            $products = array_values(array_filter($products, function ($product) use ($q, $categoryFilter) {
                $ok = true;
                if ($q !== '') {
                    $ok = stripos($product['title'] ?? '', $q) !== false
                        || stripos($product['description'] ?? '', $q) !== false;
                }
                if ($ok && $categoryFilter !== '' && $categoryFilter !== 'all') {
                    $ok = ($product['category'] ?? '') === $categoryFilter;
                }
                return $ok;
            }));
        }

        if ($sort === 'price_asc') {
            usort($products, function ($a, $b) {
                return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
            });
        } elseif ($sort === 'price_desc') {
            usort($products, function ($a, $b) {
                return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
            });
        } elseif ($sort === 'date_asc') {
            usort($products, function ($a, $b) {
                return (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0);
            });
        } elseif ($sort === 'date_desc') {
            usort($products, function ($a, $b) {
                return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
            });
        }

        if ($request->query('ajax')) {
            return response()->json(array_values($products));
        }

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'q' => $q,
            'categoryFilter' => $categoryFilter,
            'sort' => $sort,
            'sizeLabels' => ProductSizeInventory::sizeLabels(),
        ]);
    }

    private function resolveImageUrl(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        // Absolute and external URLs: cache locally to avoid remote blocks, but fall back if fetch fails.
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $trimmed) || str_starts_with($trimmed, '//') || str_starts_with($trimmed, 'www.')) {
            $url = $trimmed;
            if (str_starts_with($url, '//')) {
                $url = 'https:' . $url;
            } elseif (str_starts_with($url, 'www.')) {
                $url = 'https://' . $url;
            }
            return $this->cacheExternalImage($url);
        }

        if (str_starts_with($trimmed, 'data:')) {
            return $trimmed;
        }

        // Normalize relative paths against the current request base (supports subfolders and different hosts/ports).
        $normalized = str_replace('\\', '/', $trimmed);
        $normalized = ltrim($normalized, '/');

        $base = '';
        if (function_exists('request') && request()) {
            $base = rtrim(request()->getSchemeAndHttpHost() . request()->getBaseUrl(), '/');
        }

        if ($base !== '') {
            return $base . '/' . $normalized;
        }

        // Fallback to asset() if request is unavailable (e.g., in CLI).
        return asset($normalized);
    }

    private function cacheExternalImage(string $url): string
    {
        if (! preg_match('#^https?://#i', $url)) {
            return $url;
        }

        $hash = sha1($url);
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext === '') {
            $ext = 'jpg';
        }

        $relative = 'cache/product-images/' . $hash . '.' . $ext;
        $publicDir = public_path('cache/product-images');
        $publicPath = public_path($relative);
        $publicUrl = asset($relative);

        if (! is_dir($publicDir)) {
            @mkdir($publicDir, 0775, true);
        }

        if (is_file($publicPath) && filesize($publicPath) > 0) {
            return $publicUrl;
        }

        try {
            $context = stream_context_create([
                'http' => ['timeout' => 4],
                'https' => ['timeout' => 4],
            ]);
            $data = @file_get_contents($url, false, $context);
            if ($data !== false && strlen($data) > 0) {
                @file_put_contents($publicPath, $data);
                return $publicUrl;
            }
        } catch (\Throwable $e) {
            // Ignore fetch errors and fall back to the remote URL.
        }

        return $url;
    }
}
