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

        // বর্তমান ক্যাটাগরি আইডি খুঁজে বের করা
        $categoryId = $formData['category_id'] ?? null;
        $category = Category::find($categoryId);

        // সেফটি চেক: যদি খাতটি স্টক ট্র্যাক না করে, কিন্তু ডাটাবেজে আগের কোনো স্টক রেকর্ড থেকে থাকে
        if (!$category || !$category->is_stock) {
            if ($record->stockItem) {
                $record->stockItem()->delete();
            }
        }
    }
}