<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class LedgerOverview extends BaseWidget
{
    // লাইভ ডেটা রিফ্রেশ রেট (প্রতি ১৫ সেকেন্ডে ড্যাশবোর্ড অটো আপডেট হবে)
    protected static ?string $pollingInterval = '15s';

    // উইজেটটিকে গ্রিড লুকে মোবাইল এবং ডেস্কটপে সুন্দরভাবে রি-অ্যারেঞ্জ করার বুটস্ট্র্যাপ কলাম লেআউট
    protected int | array | string $columnSpan = 'full';

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
        
        $todayExtraCost = (float) DB::table('stock_items')
            ->join('transactions', 'stock_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.date', $today)
            ->sum('stock_items.extra_cost');
            
        $todayTotalDebit = $todayDebitAmount + $todayExtraCost;

        return [
            // ==========================================
            // 🔥 নতুন নেভিগেশনাল ক্লিকযোগ্য কার্ড সেকশন (সার্বজনীন রো ১)
            // ==========================================

            // 🟢 কার্ড এ: জমা খাতা (Clickable)
            Stat::make(' ', '➡️ জমা খাতা')
                ->description('সকল জমার বিবরণী দেখতে এখানে ক্লিক করুন')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->color('success')
                ->url(url('admin/transactions/credits')) // জমা খাতার কাস্টম পেজ লিংক
                ->extraAttributes([
                    'class' => 'cursor-pointer transition hover:scale-[1.02] duration-200 border-l-4 border-emerald-500 rounded-xl shadow-sm',
                    'style' => 'background-color: rgba(16, 185, 129, 0.08); font-weight: 900;',
                ]),

            // 🔴 কার্ড বি: খরচ খাতা (Clickable)
            Stat::make(' ', '➡️ খরচ খাতা')
                ->description('সকল খরচের বিবরণী দেখতে এখানে ক্লিক করুন')
                ->descriptionIcon('heroicon-m-arrow-down-right')
                ->color('danger')
                ->url(url('admin/transactions/debits')) // খরচ খাতার কাস্টম পেজ লিংক
                ->extraAttributes([
                    'class' => 'cursor-pointer transition hover:scale-[1.02] duration-200 border-l-4 border-rose-500 rounded-xl shadow-sm',
                    'style' => 'background-color: rgba(239, 68, 68, 0.08); font-weight: 900;',
                ]),

            // ==========================================
            // 📊 বিদ্যমান ৩টি লাইভ স্ট্যাটাস কার্ড (সার্বজনীন রো ২)
            // ==========================================

            // 💳 কার্ড ১: মোট জমা ও বর্তমান ব্যালেন্স
            Stat::make('বর্তমান ক্যাশ ব্যালেন্স', '৳ ' . number_format($currentBalance))
                ->description('মোট জমা: ৳' . number_format($totalCredit))
                ->descriptionIcon('heroicon-m-wallet')
                ->color($currentBalance >= 0 ? 'success' : 'danger'),

            // 💸 কার্ড ২: সর্বমোট খরচ
            Stat::make('সর্বমোট খরচ (ডেবিট)', '৳ ' . number_format($finalTotalDebit))
                ->description('মূল খরচ: ৳' . number_format($totalDebitAmount) . ' | অতিরিক্ত: ৳' . number_format($totalExtraCost))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            // 📅 কার্ড ৩: আজকের লাইভ ট্র্যাকিং
            Stat::make('আজকের তারিখ', Carbon::now()->format('d M, Y'))
                ->description('আজকের জমা: ৳' . number_format($todayCredit) . ' | খরচ: ৳' . number_format($todayTotalDebit))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}