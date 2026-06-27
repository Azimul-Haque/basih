<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('stock_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['buy', 'sell']); // ক্রয় নাকি বিক্রয়
            $table->decimal('quantity', 12, 2); // পরিমাণ
            $table->decimal('extra_cost', 10, 2)->default(0.00); // অতিরিক্ত খরচ (পরিবহন/লেবার)
            $table->decimal('unit_price', 12, 2); // প্রতি এককের প্রকৃত মূল্য
            $table->decimal('profit_per_unit', 12, 2)->default(0.00); // প্রতি এককে লাভ (শুধুমাত্র বিক্রয়ের সময়)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
