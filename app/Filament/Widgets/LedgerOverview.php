<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Widget; // 🔥 পরিবর্তন: StatsOverviewWidget এর বদলে সরাসরি Widget
use Carbon\Carbon;

class LedgerOverview extends Widget
{
    protected static ?string $pollingInterval = '15s';

    protected int | array | string $columnSpan = 'full';

    // 🔥 আমাদের কাস্টম ব্লেড ভিউ ফাইল ম্যাপ করা হলো
    protected static string $view = 'filament.widgets.ledger-overview';

    public function getViewData(): array
    {
        // ১. মোট জমা
        $totalCredit = (float) Transaction::where('type', 'credit')->sum('amount');

        // ২. মোট সাধারণ খরচ
        $totalDebitAmount = (float) Transaction::where('type', 'debit')->sum('amount');

        // ৩. স্টক আইটেম থেকে মোট অতিরিক্ত খরচ
        $totalExtraCost = (DB::table('stock_items')->sum('extra_cost') ?? 0);

        // ৪. চূড়ান্ত মোট খরচ
        $finalTotalDebit = $totalDebitAmount + $totalExtraCost;

        // ৫. বর্তমান ব্যালেন্স
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
            'currentBalance' => $currentBalance,
            'totalCredit' => $totalCredit,
            'finalTotalDebit' => $finalTotalDebit,
            'totalDebitAmount' => $totalDebitAmount,
            'totalExtraCost' => $totalExtraCost,
            'todayCredit' => $todayCredit,
            'todayTotalDebit' => $todayTotalDebit,
            'todayDate' => Carbon::now()->format('d M, Y'),
        ];
    }
}