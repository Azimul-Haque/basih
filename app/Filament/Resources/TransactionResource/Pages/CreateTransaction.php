<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record; 
        $formData = $this->form->getRawState(); 

        // ফিলামেন্ট রিলেশন সেকশন থেকে ডাটা রিড করা
        $stockData = data_get($formData, 'stockItem', []);
        $quantity = (float) (data_get($stockData, 'quantity') ?? 0);
        $extraCost = (float) (data_get($stockData, 'extra_cost') ?? 0);
        $baseAmount = (float) ($formData['amount'] ?? 0);

        $category = Category::find($formData['category_id'] ?? null);

        if ($category && $category->is_stock) {
            $finalAmount = $baseAmount + $extraCost;

            $record->update(['amount' => $finalAmount]);
            $unitPrice = $quantity > 0 ? ($finalAmount / $quantity) : 0;

            // ফিলামেন্ট অলরেডি রো তৈরি করে ফেলেছে, আমরা জাস্ট unit_price এবং টাইমিং আপডেট করে দেব
            \DB::table('stock_items')
                ->where('transaction_id', $record->id)
                ->update([
                    'unit_price' => $unitPrice,
                    'updated_at' => now(),
                ]);
        }
    }
}