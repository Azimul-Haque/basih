<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockType extends Model
{
    protected $fillable = ['name'];

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }
}