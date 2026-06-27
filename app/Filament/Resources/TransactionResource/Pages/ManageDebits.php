<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageDebits extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected static ?string $title = 'খরচ খাতা (Debit)';

    // 🔥 খরচ বা ডেবিট ডাটা ফিল্টার
    protected function modifyQueryUsing(Builder $query): Builder
    {
        return $query->where('type', 'debit');
    }
}