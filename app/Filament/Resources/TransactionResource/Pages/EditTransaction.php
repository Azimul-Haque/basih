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

        $quantity = (float) (data_get($formData, 'stockItem.quantity') ?? $formData['quantity'] ?? 0);
        $extraCost = (float) (data_get($formData, 'stockItem.extra_cost') ?? $formData['extra_cost'] ?? 0);
        $baseAmount = (float) ($formData['amount'] ?? 0);

        $category = Category::find($formData['category_id'] ?? null);

        if ($category && $category->is_stock) {
            
            // 🔥 এডিটের সময়ও বেস অ্যামাউন্ট + অতিরিক্ত খরচ যোগ করা
            $finalAmount = $baseAmount + $extraCost;

            $record->update([
                'amount' => $finalAmount
            ]);

            $unitPrice = $quantity > 0 ? ($finalAmount / $quantity) : 0;

            DB::table('stock_items')->updateOrInsert(
                ['transaction_id' => $record->id],
                [
                    'unit_id'    => data_get($formData, 'stockItem.unit_id') ?? $formData['unit_id'] ?? null,
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                    'extra_cost' => $extraCost,
                    'updated_at' => now(),
                ]
            );
        } else {
            DB::table('stock_items')->where('transaction_id', $record->id)->delete();
        }
    }
}