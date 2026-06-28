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

    public function mount(): void
    {
        parent::mount();

        // ইউআরএল থেকে 'type' এর ভ্যালু নেওয়া (debit অথবা credit)
        $urlType = request()->query('type');

        if (in_array($urlType, ['credit', 'debit'])) {
            // ফিলামেন্ট ফর্মের স্টেট-এ টাইপটি ইনজেক্ট করে দেওয়া হলো
            $this->form->fill([
                'date' => now()->toDateString(),
                'type' => $urlType,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        // 🔥 লেনদেনের ধরণ অনুযায়ী নির্দিষ্ট পাতায় রিডাইরেক্ট হবে
        if ($this->record->type === 'credit') {
            return $resource::getUrl('credits');
        }

        return $resource::getUrl('debits');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ইউআরএল থেকে টাইপ রিড করে ফর্মে পুশ করা
        if (request()->has('type')) {
            $data['type'] = request()->query('type');
        }
        return $data;
    }
}