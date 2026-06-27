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
        // ১. লেনদেন তৈরি হওয়ার পর স্টক ইনসার্ট
        static::created(function ($transaction) {
            $formData = request()->input('components.0.updates') 
                ?? request()->input('serverMemo.data') 
                ?? request()->all();

            $quantity = (float) (request()->input('quantity') ?? data_get($formData, 'quantity') ?? data_get($_REQUEST, 'quantity') ?? 0);
            $unitId = request()->input('unit_id') ?? data_get($formData, 'unit_id') ?? data_get($_REQUEST, 'unit_id');
            $extraCost = (float) (request()->input('extra_cost') ?? data_get($formData, 'extra_cost') ?? data_get($_REQUEST, 'extra_cost', 0));

            // 🔥 unit_price হিসাব: মোট টাকার অংক / মালের পরিমাণ (যদি পরিমাণ ০ থেকে বেশি হয়)
            $amount = (float) ($transaction->amount ?? request()->input('amount') ?? data_get($formData, 'amount') ?? 0);
            $unitPrice = $quantity > 0 ? ($amount / $quantity) : 0;

            if ($transaction->category && $transaction->category->is_stock) {
                DB::table('stock_items')->insert([
                    'transaction_id' => $transaction->id,
                    'unit_id'        => $unitId,
                    'quantity'       => $quantity,
                    'unit_price'     => $unitPrice, // কলামের ইরোর দূর করার জন্য অটো-ক্যালকুলেটেড ভ্যালু
                    'extra_cost'     => $extraCost,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        });

        // ২. লেনদেন আপডেট বা এডিট হওয়ার পর স্টক আপডেট
        static::updated(function ($transaction) {
            $formData = request()->input('components.0.updates') ?? request()->all();
            
            $quantity = (float) (request()->input('quantity') ?? data_get($formData, 'quantity') ?? 0);
            $unitId = request()->input('unit_id') ?? data_get($formData, 'unit_id');
            $extraCost = (float) (request()->input('extra_cost') ?? data_get($formData, 'extra_cost', 0));

            $amount = (float) ($transaction->amount ?? request()->input('amount') ?? 0);
            $unitPrice = $quantity > 0 ? ($amount / $quantity) : 0;

            if ($transaction->category && $transaction->category->is_stock) {
                DB::table('stock_items')->updateOrInsert(
                    ['transaction_id' => $transaction->id],
                    [
                        'unit_id'    => $unitId,
                        'quantity'   => $quantity,
                        'unit_price' => $unitPrice,
                        'extra_cost' => $extraCost,
                        'updated_at' => now(),
                    ]
                );
            } else {
                DB::table('stock_items')->where('transaction_id', $transaction->id)->delete();
            }
        });

        // ৩. লেনদেন ডিলিট হলে স্টক ডিলিট
        static::deleted(function ($transaction) {
            DB::table('stock_items')->where('transaction_id', $transaction->id)->delete();
        });
    }
}