<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use App\Filament\Resources\TransactionResource;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'ড্যাশবোর্ড';
    protected static ?string $title = 'ড্যাশবোর্ড';
    protected static ?int $navigationSort = 1;

    // 🔥 ড্যাশবোর্ডের ডান কোণায় প্যারালাল বাটন যুক্ত করা হলো
    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_transaction')
                ->label('লেনদেন এন্ট্রি করুন')
                ->icon('heroicon-m-plus')
                // ->color('amber')
                ->url(fn (): string => url('admin/transactions/create')), // লেনদেন ক্রিয়েট পেজের ইউআরএল
        ];
    }
}