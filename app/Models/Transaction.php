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
        // 1. On creation
        static::created(function ($transaction) {
            // Gather input payloads across livewire nested data states safely
            $rawRequest = request()->all();
            $livewireData = data_get($rawRequest, 'components.0.snapshot.data.data') 
                ?? data_get($rawRequest, 'serverMemo.data.data') 
                ?? data_get($rawRequest, 'data', []);

            // Safely pull fields with direct fallbacks to the absolute top request payload
            $quantity = (float) (data_get($livewireData, 'quantity') ?? request()->input('quantity') ?? 0);
            $unitId = data_get($livewireData, 'unit_id') ?? request()->input('unit_id');
            $extraCost = (float) (data_get($livewireData, 'extra_cost') ?? request()->input('extra_cost') ?? 0);

            // Auto calculate unit pricing safely
            $amount = (float) ($transaction->amount ?? data_get($livewireData, 'amount') ?? 0);
            $unitPrice = $quantity > 0 ? ($amount / $quantity) : 0;

            if ($transaction->category && $transaction->category->is_stock) {
                // 🔥 BACKUP SAFEGUARD: If unitId is somehow still empty, find the absolute first global unit ID as a structural fallback
                if (empty($unitId)) {
                    $unitId = \DB::table('units')->value('id');
                }

                \DB::table('stock_items')->insert([
                    'transaction_id' => $transaction->id,
                    'unit_id'        => $unitId, 
                    'quantity'       => $quantity,
                    'unit_price'     => $unitPrice,
                    'extra_cost'     => $extraCost,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        });

        // 2. On update
        static::updated(function ($transaction) {
            $rawRequest = request()->all();
            $livewireData = data_get($rawRequest, 'components.0.snapshot.data.data') 
                ?? data_get($rawRequest, 'data', []);

            $quantity = (float) (data_get($livewireData, 'quantity') ?? request()->input('quantity') ?? 0);
            $unitId = data_get($livewireData, 'unit_id') ?? request()->input('unit_id');
            $extraCost = (float) (data_get($livewireData, 'extra_cost') ?? request()->input('extra_cost') ?? 0);

            $amount = (float) ($transaction->amount ?? data_get($livewireData, 'amount') ?? 0);
            $unitPrice = $quantity > 0 ? ($amount / $quantity) : 0;

            if ($transaction->category && $transaction->category->is_stock) {
                if (empty($unitId)) {
                    $unitId = \DB::table('units')->value('id');
                }

                \DB::table('stock_items')->updateOrInsert(
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
                \DB::table('stock_items')->where('transaction_id', $transaction->id)->delete();
            }
        });

        // 3. On deletion
        static::deleted(function ($transaction) {
            \DB::table('stock_items')->where('transaction_id', $transaction->id)->delete();
        });
    }
}