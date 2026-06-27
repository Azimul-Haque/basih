<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    // 🔥 ADD THIS METHOD TO REDIRECT AFTER CREATING
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}