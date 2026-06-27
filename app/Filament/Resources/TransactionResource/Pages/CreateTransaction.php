<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        // 🔥 লেনদেনের ধরণ অনুযায়ী নির্দিষ্ট পাতায় রিডাইরেক্ট হবে
        if ($this->record->type === 'credit') {
            return $resource::getUrl('credits');
        }

        return $resource::getUrl('debits');
    }
}