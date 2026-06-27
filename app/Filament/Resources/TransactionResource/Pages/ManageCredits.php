<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageCredits extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = TransactionResource::class;

    protected static string $view = 'filament.resources.transaction-resource.pages.manage-credits';

    protected static ?string $title = 'জমা খাতা (Credit)';

    public function table(Table $table): Table
    {
        return TransactionResource::table($table)
            ->modifyQueryUsing(fn ($query) => $query->where('type', 'credit'))
            ->heading('সকল জমার তালিকা');
    }
}