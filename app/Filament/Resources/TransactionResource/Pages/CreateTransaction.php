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

        // ১. ফর্ম থেকে মূল ডাটাগুলো নেওয়া
        $quantity = (float) (data_get($formData, 'stockItem.quantity') ?? $formData['quantity'] ?? 0);
        $extraCost = (float) (data_get($formData, 'stockItem.extra_cost') ?? $formData['extra_cost'] ?? 0);
        $baseAmount = (float) ($formData['amount'] ?? 0);

        // ক্যাটাগরি চেক করা
        $category = Category::find($formData['category_id'] ?? null);

        if ($category && $category->is_stock) {
            
            // ২. 🔥 অতিরিক্ত খরচ মূল অ্যামাউন্টের সাথে যোগ করে ফাইনাল অ্যামাউন্ট বের করা (যেহেতু এটি ডেবিট)
            $finalAmount = $baseAmount + $extraCost;

            // ৩. লেনদেন (Transaction) টেবিলের amount কলামটি ডাটাবেজে আপডেট করে দেওয়া
            $record->update([
                'amount' => $finalAmount
            ]);

            // ৪. unit_price হিসাব: চূড়ান্ত মোট টাকা / মালের পরিমাণ
            $unitPrice = $quantity > 0 ? ($finalAmount / $quantity) : 0;

            // ৫. stock_items টেবিলে ইনসার্ট করা
            DB::table('stock_items')->insert([
                'transaction_id' => $record->id,
                'unit_id'        => data_get($formData, 'stockItem.unit_id') ?? $formData['unit_id'] ?? null,
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'extra_cost'     => $extraCost,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}