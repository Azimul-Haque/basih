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
        
        // 🔥 This method bypasses hidden field stripping and grabs exactly what the user typed in the viewport
        $formData = $this->form->getRawState(); 

        $category = Category::find($formData['category_id'] ?? null);

        if ($category && $category->is_stock) {
            $quantity = (float) ($formData['quantity'] ?? 0);
            $amount = (float) ($record->amount ?? $formData['amount'] ?? 0);
            
            // Calculate the unit price precisely
            $unitPrice = $quantity > 0 ? ($amount / $quantity) : 0;

            DB::table('stock_items')->insert([
                'transaction_id' => $record->id,
                'unit_id'        => $formData['unit_id'] ?? null,
                'quantity'       => $quantity,
                'unit_price'     => $unitPrice,
                'extra_cost'     => (float) ($formData['extra_cost'] ?? 0),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}