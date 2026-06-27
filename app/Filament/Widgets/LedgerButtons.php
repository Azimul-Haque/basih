<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LedgerButtons extends BaseWidget
{
    protected int | array | string $columnSpan = 'full';

    // 🔥 মোবাইলে ২ কলাম (পাশাপাশি হাফ-হাফ), ডেস্কটপেও ২ কলাম
    protected int | array | string $columns = [
        'default' => 2,
        'md' => 2,
    ];

    protected function getStats(): array
    {
        return [
            // 🟢 কার্ড ১: জমা খাতা (মোবাইলে ৫০%)
            Stat::make(' ', '➡️ জমা খাতা')
                ->description('সকল জমার বিবরণী দেখতে এখানে ক্লিক করুন')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->color('success')
                ->url(url('admin/transactions/credits'))
                ->extraAttributes([
                    'class' => 'cursor-pointer transition hover:scale-[1.02] duration-200 border-l-4 border-emerald-500 rounded-xl shadow-sm',
                    'style' => 'background-color: rgba(16, 185, 129, 0.08); font-weight: 900;',
                ]),

            // 🔴 কার্ড ২: খরচ খাতা (মোবাইলে ৫০%)
            Stat::make(' ', '➡️ খরচ খাতা')
                ->description('সকল খরচের বিবরণী দেখতে এখানে ক্লিক করুন')
                ->descriptionIcon('heroicon-m-arrow-down-right')
                ->color('danger')
                ->url(url('admin/transactions/debits'))
                ->extraAttributes([
                    'class' => 'cursor-pointer transition hover:scale-[1.02] duration-200 border-l-4 border-rose-500 rounded-xl shadow-sm',
                    'style' => 'background-color: rgba(239, 68, 68, 0.08); font-weight: 900;',
                ]),
        ];
    }
}