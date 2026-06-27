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

        $stockData = data_get($formData, 'stockItem', []);
        $extraCost = (float) (data_get($stockData, 'extra_cost') ?? 0);
        $baseAmount = (float) ($formData['amount'] ?? 0);

        $category = Category::find($formData['category_id'] ?? null);

        if ($category && $category->is_stock && $extraCost > 0) {
            // অতিরিক্ত খরচ মূল অ্যামাউন্টের সাথে যোগ করে ফাইনাল অ্যামাউন্ট আপডেট করা
            $record->update([
                'amount' => $baseAmount + $extraCost
            ]);
        }
    }
}