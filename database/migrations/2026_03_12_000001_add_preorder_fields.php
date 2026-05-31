<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
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
    }

    public function down(): void
    {
        // Intentionally left blank to avoid dropping existing columns.
    }
};