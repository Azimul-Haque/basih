<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageDebits extends Page implements HasTable
{
    use IntersectsWithTables;

    protected static string $resource = TransactionResource::class;

    protected static string $view = 'filament.resources.transaction-resource.pages.manage-debits';

    protected static ?string $title = 'খরচ খাতা (Debit)';

    public function table(Table $table): Table
    {
        // 🔥 মূল টেবিল স্ট্রাকচার কল করে শুধু 'debit' (ক্রয়/খরচ) ডাটা ফিল্টার করা হলো
        return TransactionResource::table($table)
            ->modifyQueryUsing(fn ($query) => $query->where('type', 'debit'))
            ->heading('সকল খরচের তালিকা');
    }
}