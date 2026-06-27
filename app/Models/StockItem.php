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
            // 🔥 ডেটাবেজে ডাটা ইনসার্ট বা আপডেট হওয়ার ঠিক পূর্ব মুহূর্তে এটি রান করবে
            static::saving(function ($stockItem) {
                $quantity = (float) $stockItem->quantity;

                // যদি ট্রানজেকশন রিলেশন লোড করা থাকে, তবে তার অ্যামাউন্ট নেওয়া, অন্যথায় রিকোয়েস্ট থেকে নেওয়া
                $transaction = $stockItem->transaction;
                $amount = $transaction ? (float) $transaction->amount : (float) request()->input('amount', 0);
                $extraCost = (float) $stockItem->extra_cost;

                // যদি এটি ক্রিয়েট হওয়ার সময় রান হয় এবং অ্যামাউন্ট এখনো আপডেট না হয়ে থাকে, 
                // তবে বেস অ্যামাউন্ট + অতিরিক্ত খরচ যোগ করে সঠিক অ্যামাউন্ট ধরে নেওয়া
                if ($transaction && $transaction->wasRecentlyCreated === false && !str_contains(request()->url(), 'edit')) {
                    $amount += $extraCost;
                }

                // unit_price হিসাব: মোট টাকার অংক / মালের পরিমাণ
                $stockItem->unit_price = $quantity > 0 ? ($amount / $quantity) : 0;
            });
        }
}