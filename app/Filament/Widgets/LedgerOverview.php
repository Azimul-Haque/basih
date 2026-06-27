<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class LedgerOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected int | array | string $columnSpan = 'full';

    // 🔥 মোবাইলে ১ কলাম (প্রত্যেকে ফুল উইডথ), ডেস্কটপে ৩ কলাম (পাশাপাশি সমান ৩ ভাগ)
    protected int | array | string $columns = [
        'default' => 1,
        'md' => 3,
    ];

    protected function getStats(): array
    {
        $totalCredit = (float) Transaction::where('type', 'credit')->sum('amount');
        $totalDebitAmount = (float) Transaction::where('type', 'debit')->sum('amount');
        $totalExtraCost = (DB::table('stock_items')->sum('extra_cost') ?? 0);
        $finalTotalDebit = $totalDebitAmount + $totalExtraCost;
        $currentBalance = $totalCredit - $finalTotalDebit;

        $today = Carbon::today()->toDateString();
        $todayCredit = (float) Transaction::where('type', 'credit')->whereDate('date', $today)->sum('amount');
        $todayDebitAmount = (float) Transaction::where('type', 'debit')->whereDate('date', $today)->sum('amount');
        
        $todayExtraCost = (float) DB::table('stock_items')
            ->join('transactions', 'stock_items.transaction_id', '=', 'transactions.id')
            ->whereDate('transactions.date', $today)
            ->sum('stock_items.extra_cost');
            
        $todayTotalDebit = $todayDebitAmount + $todayExtraCost;

        return [
            // 💳 কার্ড ১
            Stat::make('বর্তমান ক্যাশ ব্যালেন্স', '৳ ' . number_format($currentBalance))
                ->description('মোট জমা: ৳' . number_format($totalCredit))
                ->descriptionIcon('heroicon-m-wallet')
                ->color($currentBalance >= 0 ? 'success' : 'danger'),

            // 💸 কার্ড ২
            Stat::make('সর্বমোট খরচ (ডেবিট)', '৳ ' . number_format($finalTotalDebit))
                ->description('মূল খরচ: ৳' . number_format($totalDebitAmount) . ' | অতিরিক্ত: ৳' . number_format($totalExtraCost))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            // 📅 কার্ড ৩
            Stat::make('আজকের তারিখ', Carbon::now()->format('d M, Y'))
                ->description('আজকের জমা: ৳' . number_format($todayCredit) . ' | খরচ: ৳' . number_format($todayTotalDebit))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}