<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action; // 👈 এই নেমস্পেসটি নিশ্চিত করুন
use Illuminate\Database\Eloquent\Builder;

class ManageDebits extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected static ?string $title = 'খরচ খাতা (Debit)';

    protected function getTableQuery(): ?Builder
    {
        // 🔥 type='debit' ফিল্টার করার পাশাপাশি তারিখ অনুযায়ী DESC অর্ডার লক করা হলো
        return static::getResource()::getEloquentQuery()
            ->where('type', 'debit')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');
    }

    // 🔥 খরচ খাতার ডান কোণায় "খরচ করুন" বাটন
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_debit')
                ->label('খরচ করুন')
                ->icon('heroicon-m-minus')
                ->color('danger') // লাল বাটন (খরচের প্রতীক)
                ->size('lg')
                ->url(fn (): string => static::getResource()::getUrl('create', ['type' => 'debit'])),
        ];
    }
}