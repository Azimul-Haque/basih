<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action; // 👈 এই নেমস্পেসটি নিশ্চিত করুন
use Illuminate\Database\Eloquent\Builder;

class ManageCredits extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected static ?string $title = 'জমা খাতা (Credit)';

    protected function getTableQuery(): ?Builder
    {
        // 🔥 type='credit' ফিল্টার করার পাশাপাশি তারিখ অনুযায়ী DESC অর্ডার লক করা হলো
        return static::getResource()::getEloquentQuery()
            ->where('type', 'credit')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc'); // একই তারিখের একাধিক এন্ট্রি থাকলে আইডি দিয়ে নিখুঁত অর্ডার রাখার জন্য
    }

    // 🔥 জমা খাতার ডান কোণায় "জমা এন্ট্রি করুন" বাটন
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_credit')
                ->label('জমা এন্ট্রি করুন')
                ->icon('heroicon-m-plus')
                ->color('success') // সবুজ বাটন
                ->size('lg')
                ->url(fn (): string => static::getResource()::getUrl('create', ['type' => 'credit'])), 
        ];
    }
}