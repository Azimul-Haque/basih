<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItem extends Model
{
    protected $fillable = [
        'transaction_id', 'stock_type_id', 'unit_id', 
        'type', 'quantity', 'extra_cost', 'unit_price', 'profit_per_unit'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    // public function stockType(): BelongsTo
    // {
    //     return $this->belongsTo(StockType::class);
    // }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    protected static function booted()
    {
        static::saving(function ($stockItem) {
            $quantity = (float) $stockItem->quantity;
            $transaction = $stockItem->transaction;
            
            // ফর্মে ইনপুট দেওয়া বিশুদ্ধ মূল দাম (Base Amount)
            $baseAmount = $transaction ? (float) $transaction->amount : (float) request()->input('amount', 0);
            $extraCost = (float) $stockItem->extra_cost;

            // মোট প্রকৃত খরচ = মূল দাম + অতিরিক্ত খরচ
            $totalCost = $baseAmount + $extraCost;

            // unit_price হিসাব
            $stockItem->unit_price = $quantity > 0 ? ($totalCost / $quantity) : 0;

            if ($transaction) {
                // 🔥 ১. স্টক আইটেমের টাইপ ডাইনামিকালি সেট করা (debit = buy, credit = sell)
                $stockItem->type = $transaction->type === 'debit' ? 'buy' : 'sell';

                // ২. যদি বিক্রয় (sell) মোড হয় এবং unit_id খালি থাকে, তবে পূর্বের কেনা এককটি বসানো
                if (empty($stockItem->unit_id)) {
                    $categoryId = $transaction->category_id;

                    $lastPurchaseItem = self::join('transactions', 'stock_items.transaction_id', '=', 'transactions.id')
                        ->where('transactions.category_id', $categoryId)
                        ->where('transactions.type', 'debit')
                        ->orderBy('transactions.date', 'desc')
                        ->orderBy('transactions.id', 'desc')
                        ->select('stock_items.unit_id')
                        ->first();

                    if ($lastPurchaseItem) {
                        $stockItem->unit_id = $lastPurchaseItem->unit_id;
                    }
                }
            }
        });
    }
}