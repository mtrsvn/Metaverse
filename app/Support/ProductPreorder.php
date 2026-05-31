<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProductPreorder
{
    public static function ensureSchema(): bool
    {
        if (! Schema::hasTable('products')) {
            return false;
        }

        $missingProductFlag = ! Schema::hasColumn('products', 'is_preorder');
        $missingProductNote = ! Schema::hasColumn('products', 'preorder_note');

        if ($missingProductFlag || $missingProductNote) {
            Schema::table('products', function (Blueprint $table) use ($missingProductFlag, $missingProductNote) {
                if ($missingProductFlag) {
                    $table->boolean('is_preorder')->default(false);
                }
                if ($missingProductNote) {
                    $table->string('preorder_note')->nullable();
                }
            });
        }

        if (Schema::hasTable('purchases')) {
            $missingPurchaseFlag = ! Schema::hasColumn('purchases', 'is_preorder');
            $missingPurchaseNote = ! Schema::hasColumn('purchases', 'preorder_note');

            if ($missingPurchaseFlag || $missingPurchaseNote) {
                Schema::table('purchases', function (Blueprint $table) use ($missingPurchaseFlag, $missingPurchaseNote) {
                    if ($missingPurchaseFlag) {
                        $table->boolean('is_preorder')->default(false);
                    }
                    if ($missingPurchaseNote) {
                        $table->string('preorder_note')->nullable();
                    }
                });
            }
        }

        return true;
    }

    public static function decorateProductRecord(array $item): array
    {
        $item['is_preorder'] = self::normalizeFlag($item['is_preorder'] ?? false);
        $item['preorder_note'] = self::normalizeNote($item['preorder_note'] ?? '');

        return $item;
    }

    public static function isEnabledForProduct(array $item): bool
    {
        return self::normalizeFlag($item['is_preorder'] ?? false);
    }

    public static function shouldTreatAsPreorder(array $item, int $requestedQty, int $availableStock): bool
    {
        return self::isEnabledForProduct($item) && $requestedQty > max(0, $availableStock);
    }

    public static function noteOrNull(mixed $value): ?string
    {
        $note = self::normalizeNote($value);

        return $note !== '' ? substr($note, 0, 255) : null;
    }

    public static function normalizeFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    public static function normalizeNote(mixed $value): string
    {
        return trim((string) $value);
    }
}