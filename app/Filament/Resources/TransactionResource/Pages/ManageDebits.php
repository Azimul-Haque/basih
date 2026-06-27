<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageDebits extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected static ?string $title = 'খরচ খাতা (Debit)';

    // 🔥 অফিশিয়াল ফিলামেন্ট কুয়েরি আইসোলেশন বিল্ডার
    protected function getTableQuery(): ?Builder
    {
        return static::getResource()::getEloquentQuery()->where('type', 'debit');
    }
}