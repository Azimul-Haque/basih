<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // 🔥 ADD THIS METHOD TO REDIRECT AFTER SAVING EDITS
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $formData = $this->form->getRawState();

        $stockData = data_get($formData, 'stockItem', []);
        $extraCost = (float) (data_get($stockData, 'extra_cost') ?? 0);
        $baseAmount = (float) ($formData['amount'] ?? 0);

        $category = Category::find($formData['category_id'] ?? null);

        if ($category && $category->is_stock) {
            $record->update([
                'amount' => $baseAmount + $extraCost
            ]);
        } else {
            // যদি খাতের ধরণ পরিবর্তন করে নন-স্টক করা হয়, তবে স্টক রেকর্ড মুছে ফেলা
            $record->stockItem()->delete();
        }
    }
}