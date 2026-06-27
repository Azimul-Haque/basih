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
}