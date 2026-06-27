<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
}



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

    protected function afterSave(): void
    {
        $record = $this->record;
        $formData = $this->form->getRawState();

        $category = Category::find($formData['category_id'] ?? null);

        if ($category && $category->is_stock) {
            $quantity = (float) ($formData['quantity'] ?? 0);
            $amount = (float) ($record->amount ?? $formData['amount'] ?? 0);
            $unitPrice = $quantity > 0 ? ($amount / $quantity) : 0;

            DB::table('stock_items')->updateOrInsert(
                ['transaction_id' => $record->id],
                [
                    'unit_id'    => $formData['unit_id'] ?? null,
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                    'extra_cost' => (float) ($formData['extra_cost'] ?? 0),
                    'updated_at' => now(),
                ]
            );
        } else {
            // Remove stock entry if category was shifted to a non-stock option
            DB::table('stock_items')->where('transaction_id', $record->id)->delete();
        }
    }
}