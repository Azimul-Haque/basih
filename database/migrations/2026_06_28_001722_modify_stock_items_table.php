<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            // 1. 🔥 FIRST: Tear down the relational key constraint signature
            $table->dropForeign('stock_items_stock_type_id_foreign');
            
            // 2. SECOND: Safely drop the column now that it is unbound
            $table->dropColumn('stock_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->foreignId('stock_type_id')->nullable()->constrained('stock_types');
        });
    }
};
