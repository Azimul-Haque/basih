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
}