<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
}