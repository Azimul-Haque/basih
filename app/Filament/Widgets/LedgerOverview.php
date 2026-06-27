<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class LedgerOverview extends BaseWidget
{
    // লাইভ ডেটা রিফ্রেশ রেট (ঐচ্ছিক - প্রতি ১৫ সেকেন্ডে ড্যাশবোর্ড অটো আপডেট হবে)
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // ১. মোট জমা (Total Credit)
        $totalCredit = (float) Transaction::where('type', 'credit')->sum('amount');

        // ২. মোট সাধারণ খরচ (Total Debit Amount)
        $totalDebitAmount = (float) Transaction::where('type', 'debit')->sum('amount');

        // ৩. স্টক আইটেম থেকে মোট অতিরিক্ত খরচ (Total Extra Cost)
        $totalExtraCost = (DB::table('stock_items')->sum('extra_cost') ?? 0);

        // ৪. চূড়ান্ত মোট খরচ (Debit + Extra Cost)
        $finalTotalDebit = $totalDebitAmount + $totalExtraCost;

        // ৫. বর্তমান ব্যবহারযোগ্য নিখুঁত ব্যালেন্স
        $currentBalance = $totalCredit - $finalTotalDebit;

        // --- আজকের হিসাব ---
        $today = Carbon::today()->toDateString();
        
        $todayCredit = (float) Transaction::where('type', 'credit')->whereDate('date', $today)->sum('amount');
        $todayDebitAmount = (float) Transaction::where('type', 'debit')->whereDate('date', $today)->sum('amount');
        
        // আজকের অতিরিক্ত খরচ বের করার জন্য কুয়েরি
        $todayExtraCost = (float) DB::table('stock_items')
            ->join('transactions', 'stock_items.transaction_id', '=', 'transactions.id')
            ->whereDate('transactions.date', $today)
            ->sum('stock_items.extra_cost');
            
        $todayTotalDebit = $todayDebitAmount + $todayExtraCost;

        return [
            // 💳 কার্ড ১: মোট জমা ও বর্তমান ব্যালেন্স
            Stat::make('বর্তমান ক্যাশ ব্যালেন্স: ৳', '৳ ' . number_format($currentBalance))
                ->description('Total Capital / জমা ' . number_format($totalCredit))
                ->descriptionIcon('heroicon-m-wallet')
                ->color($currentBalance >= 0 ? 'success' : 'danger'),

            // 💸 কার্ড ২: মোট খরচ (অতিরিক্ত খরচসহ)
            Stat::make('সর্বমোট খরচ (ডেবিট)', '৳ ' . number_format($finalTotalDebit))
                ->description('মূল খরচ: ৳' . number_format($totalDebitAmount) . ' | অতিরিক্ত: ৳' . number_format($totalExtraCost))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            // 📅 কার্ড ৩: আজকের তারিখ এবং লাইভ ট্র্যাকিং
            Stat::make('আজকের তারিখ', Carbon::now()->format('d M, Y'))
                ->description('আজকের জমা: ৳' . number_format($todayCredit) . ' | আজকের খরচ: ৳' . number_format($todayTotalDebit))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}