<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        $addedDisplayOrder = false;

        if (!Schema::hasColumn('products', 'display_order')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('display_order')->default(0);
            });
            $addedDisplayOrder = true;
        }

        if (!Schema::hasColumn('products', 'discount')) {
            Schema::table('products', function (Blueprint $table) {
                $table->float('discount')->default(0);
            });
        }

        if (!Schema::hasColumn('products', 'stock')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('stock')->default(0);
            });
        }

        if ($addedDisplayOrder) {
            DB::statement('UPDATE products SET display_order = id WHERE display_order = 0 OR display_order IS NULL');
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid dropping existing columns.
    }
};
