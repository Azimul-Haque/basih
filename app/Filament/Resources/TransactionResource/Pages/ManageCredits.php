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
        return static::getResource()::getEloquentQuery()->where('type', 'credit');
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