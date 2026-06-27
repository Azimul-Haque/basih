<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageDebits extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = TransactionResource::class;

    // 🔥 এটি যুক্ত করুন যেন ফিলামেন্ট রাউটার পেজটিকে রিসোর্সের মেম্বার হিসেবে চেনে
    protected static ?string $navigationLabel = 'জমা খাতা';

    protected static string $view = 'filament.resources.transaction-resource.pages.manage-debits';

    protected static ?string $title = 'খরচ খাতা (Debit)';

    public function table(Table $table): Table
    {
        return TransactionResource::table($table)
            ->modifyQueryUsing(fn ($query) => $query->where('type', 'debit'))
            ->heading('সকল খরচের তালিকা');
    }
}