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

    // 🔥 গ্লোবাল গ্রিড মোবাইলের জন্য ২ কলাম এবং ডেস্কটপের জন্য ৬ কলামে ভাগ করা হলো
    protected int | array | string $columns = [
        'default' => 2,
        'md' => 6,
        'lg' => 6,
    ];

    protected function getStats(): array
    {
        // ১. মোট জমা
        $totalCredit = (float) Transaction::where('type', 'credit')->sum('amount');

        // ২. মোট সাধারণ খরচ
        $totalDebitAmount = (float) Transaction::where('type', 'debit')->sum('amount');

        // ৩. স্টক আইটেম থেকে মোট অতিরিক্ত খরচ
        $totalExtraCost = (DB::table('stock_items')->sum('extra_cost') ?? 0);

        // ৪. চূড়ান্ত মোট খরচ
        $finalTotalDebit = $totalDebitAmount + $totalExtraCost;

        // ৫. বর্তমান ব্যবহারযোগ্য ব্যালেন্স
        $currentBalance = $totalCredit - $finalTotalDebit;

        // --- আজকের হিসাব ---
        $today = Carbon::today()->toDateString();
        
        $todayCredit = (float) Transaction::where('type', 'credit')->whereDate('date', $today)->sum('amount');
        $todayDebitAmount = (float) Transaction::where('type', 'debit')->whereDate('date', $today)->sum('amount');
        
        $todayExtraCost = (float) DB::table('stock_items')
            ->join('transactions', 'stock_items.transaction_id', '=', 'transactions.id')
            ->whereDate('transactions.date', $today)
            ->sum('stock_items.extra_cost');
            
        $todayTotalDebit = $todayDebitAmount + $todayExtraCost;

        return [
            // ==========================================
            // 🟢 রো ১ (মোবাইলে পাশাপাশি হাফ-হাফ, ডেস্কটপে ৩ কলাম করে সমান অর্ধেক)
            // ==========================================
            Stat::make(' ', '➡️ জমা খাতা')
                ->description('সকল জমার বিবরণী দেখতে এখানে ক্লিক করুন')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->color('success')
                ->url(url('admin/transactions/credits'))
                ->extraAttributes([
                    'class' => 'cursor-pointer transition hover:scale-[1.02] duration-200 border-l-4 border-emerald-500 rounded-xl shadow-sm',
                    'style' => 'background-color: rgba(16, 185, 129, 0.08); font-weight: 900;',
                ])
                ->columnSpan([
                    'default' => 1, // মোবাইলে ২ কলামের ১ কলাম নিবে (পাশাপাশি হাফ)
                    'md' => 3,      // ডেস্কটপে ৬ কলামের ৩ কলাম নিবে (পাশাপাশি হাফ)
                ]),

            Stat::make(' ', '➡️ খরচ খাতা')
                ->description('সকল খরচের বিবরণী দেখতে এখানে ক্লিক করুন')
                ->descriptionIcon('heroicon-m-arrow-down-right')
                ->color('danger')
                ->url(url('admin/transactions/debits'))
                ->extraAttributes([
                    'class' => 'cursor-pointer transition hover:scale-[1.02] duration-200 border-l-4 border-rose-500 rounded-xl shadow-sm',
                    'style' => 'background-color: rgba(239, 68, 68, 0.08); font-weight: 900;',
                ])
                ->columnSpan([
                    'default' => 1, // মোবাইলে ২ কলামের ১ কলাম নিবে (পাশাপাশি হাফ)
                    'md' => 3,      // ডেস্কটপে ৬ কলামের ৩ কলাম নিবে (পাশাপাশি হাফ)
                ]),

            // ==========================================
            // 📊 রো ২ (মোবাইলে প্রত্যেকে ফুল উইডথ, ডেস্কটপে সুন্দর ৩টি সমান কলাম)
            // ==========================================
            Stat::make('বর্তমান ক্যাশ ব্যালেন্স', '৳ ' . number_format($currentBalance))
                ->description('মোট জমা: ৳' . number_format($totalCredit))
                ->descriptionIcon('heroicon-m-wallet')
                ->color($currentBalance >= 0 ? 'success' : 'danger')
                ->columnSpan([
                    'default' => 2, // মোবাইলে ২ কলামের ২ কলামই নিবে (Full Width)
                    'md' => 2,      // ডেস্কটপে ৬ কলামের ২ কলাম নিবে (১/৩ অংশ)
                ]),

            Stat::make('সর্বমোট খরচ (ডেবিট)', '৳ ' . number_format($finalTotalDebit))
                ->description('মূল খরচ: ৳' . number_format($totalDebitAmount) . ' | অতিরিক্ত: ৳' . number_format($totalExtraCost))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->columnSpan([
                    'default' => 2, // মোবাইলে Full Width
                    'md' => 2,      // ডেস্কটপে ১/৩ অংশ
                ]),

            Stat::make('আজকের তারিখ', Carbon::now()->format('d M, Y'))
                ->description('আজকের জমা: ৳' . number_format($todayCredit) . ' | খরচ: ৳' . number_format($todayTotalDebit))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info')
                ->columnSpan([
                    'default' => 2, // মোবাইলে Full Width
                    'md' => 2,      // ডেস্কটপে ১/৩ অংশ
                ]),
        ];
    }
}