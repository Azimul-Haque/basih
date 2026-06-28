<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class LiveSummary extends Page
{
    // 🔥 ১. সাইডবার মেনু ও পেজ কনফিগারেশন
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'একনজরে প্রতিবেদন';
    protected static ?string $navigationLabel = 'একনজরে প্রতিবেদন';
    protected static ?string $slug = 'live-summary';
    protected static ?int $navigationSort = 4; // সাইডবারে ড্যাশবোর্ডের ঠিক নিচে থাকবে

    protected static string $view = 'filament.pages.live-summary';

    /**
     * 🔥 ২. ব্লেড ভিউতে সমস্ত প্রয়োজনীয় ডাটা ডাইনামিকালি পাস করা
     */
    protected function getViewData(): array
    {
        // ১. সমস্ত আয়ের খাতসমূহের টোটাল সামারি (সবগুলো কলামের যোগফল সহ)
        $categoryCredits = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'credit')
            ->select('categories.name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get(); // 👈 এবার সব ক্যাটাগরি নেওয়া হলো (সীমাবদ্ধতা ছাড়া)

        // ২. সমস্ত খরচের খাতসমূহের টোটাল সামারি
        $categoryDebits = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'debit')
            ->select('categories.name', DB::raw('SUM(transactions.amount) as total_amount'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get();

        // ৩. স্টক রিপোর্ট এবং রিয়েল-টাইম ভ্যালুয়েশন
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
            ->where('stock_items.quantity', '>', 0) // শুধু অবশিষ্ট থাকা স্টক
            ->get();

        // মোট অবিক্রীত মালের আনুমানিক মূল্য এবং ট্রানজেকশন সামারি
        $totalStockValue = $stockReports->sum('asset_value');
        $grandTotalCredit = $categoryCredits->sum('total_amount');
        $grandTotalDebit = $categoryDebits->sum('total_amount');

        return [
            'categoryCredits' => $categoryCredits,
            'categoryDebits' => $categoryDebits,
            'stockReports' => $stockReports,
            'totalStockValue' => $totalStockValue,
            'grandTotalCredit' => $grandTotalCredit,
            'grandTotalDebit' => $grandTotalDebit,
        ];
    }
}