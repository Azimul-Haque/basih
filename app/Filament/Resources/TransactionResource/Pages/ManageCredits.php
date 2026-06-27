<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageCredits extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected static ?string $title = 'জমা খাতা (Credit)';

    // 🔥 ফিলামেন্টের নেটিভ মেথড দিয়ে কুয়েরি ফিল্টার (এখানে কোনো getPage এরর আসবে না)
    protected function modifyQueryUsing(Builder $query): Builder
    {
        return $query->where('type', 'credit');
    }
}