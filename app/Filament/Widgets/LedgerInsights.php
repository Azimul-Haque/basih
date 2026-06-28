<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\Widget;

class LedgerInsights extends Widget
{
    protected int | array | string $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s'; // প্রতি ৩০ সেকেন্ড পর পর লাইভ আপডেট হবে

    protected static string $view = 'filament.widgets.ledger-insights';

    public function getViewData(): array
    {
        // ১. খাত ভিত্তিক জমা সামারি (Top Credits)
        $categoryCredits = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'credit')
            ->select('categories.name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->take(4) // টপ ৪টি খাত
            ->get();

        // ২. খাত ভিত্তিক খরচ সামারি (Top Debits)
        $categoryDebits = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'debit')
            ->select('categories.name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->take(4) // টপ ৪টি খাত
            ->get();

        // ৩. স্টক রিপোর্ট এবং রিয়েল-টাইম ভ্যালুয়েশন
        // নোট: ট্রানজেকশনের সর্বশেষ স্টক কোয়ান্টিটি চেক করা হচ্ছে
        $stockReports = DB::table('stock_items')
            ->join('transactions', 'stock_items.transaction_id', '=', 'transactions.id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->leftJoin('units', 'stock_items.unit_id', '=', 'units.id')
            ->select(
                'categories.name as category_name',
                'units.name as unit_name',
                'stock_items.quantity',
                'stock_items.unit_price',
                DB::raw('(stock_items.quantity * stock_items.unit_price) as asset_value')
            )
            ->where('stock_items.quantity', '>', 0) // শুধু যেগুলো স্টকে আছে
            ->get();

        // মোট অবিক্রীত মালের আনুমানিক মূল্য
        $totalStockValue = $stockReports->sum('asset_value');

        return [
            'categoryCredits' => $categoryCredits,
            'categoryDebits' => $categoryDebits,
            'stockReports' => $stockReports,
            'totalStockValue' => $totalStockValue,
        ];
    }
}