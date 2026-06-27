<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    protected $fillable = ['category_id', 'date', 'type', 'amount', 'note'];

    protected $casts = [
        'date' => 'date',
    ];

    // 🔥 FORCE EXPLICIT FOREIGN KEY BINDING
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function stockItem(): HasOne
    {
        return $this->hasOne(StockItem::class, 'transaction_id');
    }

    protected static function booted()
    {
        // ১. লেনদেন তৈরি হওয়ার ঠিক পর মুহূর্তের হুক
        static::created(function ($transaction) {
            // রিকোয়েস্ট থেকে সরাসরি ডাটা রিসিভ করা (ফিলামেন্ট ফর্মে ইনপুট হাইড থাকলেও এটি কাজ করবে)
            $formData = request()->input('components.0.updates') // Livewire request fallback
                ?? request()->input('serverMemo.data') 
                ?? request()->all();

            // অথবা ফিলামেন্টের গ্লোবাল রিকোয়েস্ট স্টেট থেকে ডাটা নেওয়া
            $quantity = request()->input('quantity') ?? data_get($formData, 'quantity') ?? data_get($_REQUEST, 'quantity');
            $unitId = request()->input('unit_id') ?? data_get($formData, 'unit_id') ?? data_get($_REQUEST, 'unit_id');
            $extraCost = request()->input('extra_cost') ?? data_get($formData, 'extra_cost') ?? data_get($_REQUEST, 'extra_cost', 0);

            // ক্যাটাগরি চেক
            if ($transaction->category && $transaction->category->is_stock) {
                DB::table('stock_items')->insert([
                    'transaction_id' => $transaction->id,
                    'unit_id'        => $unitId,
                    'quantity'       => $quantity ?? 0,
                    'extra_cost'     => $extraCost ?? 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        });

        // ২. লেনদেন আপডেট বা এডিট হওয়ার পর মুহূর্তের হুক
        static::updated(function ($transaction) {
            $quantity = request()->input('quantity') ?? request()->input('components.0.updates.quantity');
            $unitId = request()->input('unit_id') ?? request()->input('components.0.updates.unit_id');
            $extraCost = request()->input('extra_cost') ?? request()->input('components.0.updates.extra_cost', 0);

            if ($transaction->category && $transaction->category->is_stock) {
                DB::table('stock_items')->updateOrInsert(
                    ['transaction_id' => $transaction->id],
                    [
                        'unit_id'    => $unitId,
                        'quantity'   => $quantity ?? 0,
                        'extra_cost' => $extraCost ?? 0,
                        'updated_at' => now(),
                    ]
                );
            } else {
                // যদি খাতের ধরন পরিবর্তন করে নন-স্টক করা হয়
                DB::table('stock_items')->where('transaction_id', $transaction->id)->delete();
            }
        });

        // ৩. লেনদেন ডিলিট হলে স্টকও স্বয়ংক্রিয়ভাবে মুছে যাবে
        static::deleted(function ($transaction) {
            DB::table('stock_items')->where('transaction_id', $transaction->id)->delete();
        });
    }
}