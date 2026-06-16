<?php

namespace App\Filament\Widgets;

use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LedgerOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Capital / জমা', '৳ 450,000')
                ->description('Total cash pool available')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Active Parties / মোট কাস্টমার', '12')
                ->description('Registered business profiles')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Pending Expenses / বাকি খরচ', '৳ 18,500')
                ->description('Unpaid balances due this week')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}