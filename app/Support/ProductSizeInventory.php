<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductSizeInventory
{
    public const SIZE_LABELS = [
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
        'xl' => 'XL',
        'xxl' => 'XXL',
        'xxxl' => 'XXXL',
    ];

    public static function sizeLabels(): array
    {
        return self::SIZE_LABELS;
    }

    public static function ensureSchema(): bool
    {
        if (! Schema::hasTable('products')) {
            return false;
        }

        if (! Schema::hasColumn('products', 'size_stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('size_stock')->nullable();
            });
        }

        if (Schema::hasTable('cart') && ! Schema::hasColumn('cart', 'product_size')) {
            Schema::table('cart', function (Blueprint $table) {
                $table->string('product_size', 20)->nullable();
            });
        }

        if (Schema::hasTable('purchases') && ! Schema::hasColumn('purchases', 'product_size')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('product_size', 20)->nullable();
            });
        }

        if (Schema::hasTable('cart') && Schema::hasColumn('cart', 'product_size')) {
            DB::table('cart')
                ->where(function ($query) {
                    $query->whereNull('product_size')
                        ->orWhere('product_size', '');
                })
                ->update(['product_size' => 'medium']);

            self::ensureCartSizeIndex();
        }

        return true;
    }

    public static function normalizeSize(?string $size): ?string
    {
        $normalized = strtolower(trim((string) $size));

        return array_key_exists($normalized, self::SIZE_LABELS) ? $normalized : null;
    }

    public static function label(?string $size): string
    {
        $normalized = self::normalizeSize($size);

        if ($normalized === null) {
            return 'No Size';
        }

        return self::SIZE_LABELS[$normalized];
    }

    public static function parseSizeStock(mixed $rawSizeStock, int $fallbackStock = 0): array
    {
        $stocks = [];
        foreach (self::SIZE_LABELS as $sizeKey => $label) {
            $stocks[$sizeKey] = 0;
        }

        $decoded = null;
        if (is_string($rawSizeStock) && trim($rawSizeStock) !== '') {
            $candidate = json_decode($rawSizeStock, true);
            if (is_array($candidate)) {
                $decoded = $candidate;
            }
        } elseif (is_array($rawSizeStock)) {
            $decoded = $rawSizeStock;
        }

        $hasExplicitVariantData = false;
        if (is_array($decoded)) {
            foreach ($decoded as $sizeKey => $stockValue) {
                $normalizedKey = self::normalizeSize((string) $sizeKey);
                if ($normalizedKey === null) {
                    continue;
                }

                $hasExplicitVariantData = true;
                $stocks[$normalizedKey] = max(0, (int) $stockValue);
            }
        }

        if (! $hasExplicitVariantData && $fallbackStock > 0) {
            $stocks['medium'] = max(0, $fallbackStock);
        }

        return $stocks;
    }

    public static function encodeSizeStock(array $sizeStock): string
    {
        $normalized = [];
        foreach (self::SIZE_LABELS as $sizeKey => $label) {
            $normalized[$sizeKey] = max(0, (int) ($sizeStock[$sizeKey] ?? 0));
        }

        return json_encode($normalized, JSON_UNESCAPED_SLASHES);
    }

    public static function totalStock(array $sizeStock): int
    {
        $total = 0;

        foreach (self::SIZE_LABELS as $sizeKey => $label) {
            $total += max(0, (int) ($sizeStock[$sizeKey] ?? 0));
        }

        return $total;
    }

    public static function stockForSize(array $sizeStock, ?string $size): int
    {
        $normalized = self::normalizeSize($size);
        if ($normalized === null) {
            return self::totalStock($sizeStock);
        }

        return max(0, (int) ($sizeStock[$normalized] ?? 0));
    }

    public static function decorateProductRecord(array $item): array
    {
        $fallbackStock = max(0, (int) ($item['stock'] ?? 0));
        $sizeStock = self::parseSizeStock($item['size_stock'] ?? null, $fallbackStock);

        $item['size_stock_map'] = $sizeStock;
        $item['stock'] = self::totalStock($sizeStock);

        return $item;
    }

    public static function adjustStockValues(object|array $product, ?string $size, int $delta): ?array
    {
        $productData = (array) $product;
        $sizeKey = self::normalizeSize($size) ?? 'medium';
        $fallbackStock = max(0, (int) ($productData['stock'] ?? 0));
        $sizeStock = self::parseSizeStock($productData['size_stock'] ?? null, $fallbackStock);

        $current = max(0, (int) ($sizeStock[$sizeKey] ?? 0));
        $next = $current + $delta;
        if ($next < 0) {
            return null;
        }

        $sizeStock[$sizeKey] = $next;

        return [
            'stock' => self::totalStock($sizeStock),
            'size_stock' => self::encodeSizeStock($sizeStock),
        ];
    }

    private static function ensureCartSizeIndex(): void
    {
        try {
            if (DB::connection()->getDriverName() !== 'mysql') {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $indexMap = self::loadIndexMap('cart');

        if (! self::hasIndexWithPrefix($indexMap, ['user_id'], ['user_product'])) {
            DB::statement('ALTER TABLE `cart` ADD INDEX `cart_user_id_index` (`user_id`)');
            $indexMap = self::loadIndexMap('cart');
        }

        if (! self::hasExactIndex($indexMap, ['user_id', 'product_id', 'product_size'], false)) {
            DB::statement(
                'ALTER TABLE `cart` ADD UNIQUE INDEX `cart_user_product_size_unique` (`user_id`, `product_id`, `product_size`)'
            );
            $indexMap = self::loadIndexMap('cart');
        }

        foreach ($indexMap as $indexName => $meta) {
            if ($indexName === 'PRIMARY' || $meta['non_unique'] !== 0) {
                continue;
            }

            ksort($meta['columns']);
            $columns = array_values($meta['columns']);
            if ($columns !== ['user_id', 'product_id']) {
                continue;
            }

            $safeName = str_replace('`', '``', $indexName);
            DB::statement("ALTER TABLE `cart` DROP INDEX `{$safeName}`");
        }
    }

    private static function loadIndexMap(string $table): array
    {
        $indexMap = [];

        foreach (DB::select("SHOW INDEX FROM `{$table}`") as $index) {
            $name = (string) $index->Key_name;
            $seq = (int) $index->Seq_in_index;

            if (! isset($indexMap[$name])) {
                $indexMap[$name] = [
                    'non_unique' => (int) $index->Non_unique,
                    'columns' => [],
                ];
            }

            $indexMap[$name]['columns'][$seq] = (string) $index->Column_name;
        }

        return $indexMap;
    }

    private static function hasExactIndex(array $indexMap, array $columns, bool $nonUnique): bool
    {
        foreach ($indexMap as $meta) {
            if ((int) $meta['non_unique'] !== ($nonUnique ? 1 : 0)) {
                continue;
            }

            ksort($meta['columns']);
            if (array_values($meta['columns']) === $columns) {
                return true;
            }
        }

        return false;
    }

    private static function hasIndexWithPrefix(array $indexMap, array $prefixColumns, array $ignoredIndexNames = []): bool
    {
        foreach ($indexMap as $indexName => $meta) {
            if (in_array($indexName, $ignoredIndexNames, true)) {
                continue;
            }

            ksort($meta['columns']);
            $columns = array_values($meta['columns']);
            if (array_slice($columns, 0, count($prefixColumns)) === $prefixColumns) {
                return true;
            }
        }

        return false;
    }
}