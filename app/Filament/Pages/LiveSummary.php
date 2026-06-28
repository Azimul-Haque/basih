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

        // ৩. স্টক রিপোর্ট এবং রিয়েল-টাইম ভ্যালুয়েশন (Current Stock Scenario: ইন - আউট)
                $stockReports = DB::table('categories')
                    ->where('categories.is_stock', true)
                    ->select([
                        'categories.id as category_id',
                        'categories.name as category_name',
                        
                        // 🔥 ইন-স্টক কোয়ান্টিটি (Debits)
                        DB::raw("(
                            SELECT COALESCE(SUM(si.quantity), 0) 
                            FROM stock_items si 
                            JOIN transactions t ON si.transaction_id = t.id 
                            WHERE t.category_id = categories.id AND t.type = 'debit'
                        ) as total_in"),
                        
                        // 🔥 আউট-স্টক কোয়ান্টিটি (Credits)
                        DB::raw("(
                            SELECT COALESCE(SUM(si.quantity), 0) 
                            FROM stock_items si 
                            JOIN transactions t ON si.transaction_id = t.id 
                            WHERE t.category_id = categories.id AND t.type = 'credit'
                        ) as total_out"),
                        
                        // সর্বশেষ আপডেটেড ইউনিট প্রাইজ (ভ্যালুয়েশনের জন্য)
                        DB::raw("(
                            SELECT si.unit_price 
                            FROM stock_items si 
                            JOIN transactions t ON si.transaction_id = t.id 
                            WHERE t.category_id = categories.id 
                            ORDER BY t.date DESC, t.id DESC 
                            LIMIT 1
                        ) as last_unit_price"),
                        
                        // সর্বশেষ ইউনিটের নাম
                        DB::raw("(
                            SELECT u.name 
                            FROM stock_items si 
                            JOIN transactions t ON si.transaction_id = t.id 
                            LEFT JOIN units u ON si.unit_id = u.id
                            WHERE t.category_id = categories.id 
                            ORDER BY t.date DESC, t.id DESC 
                            LIMIT 1
                        ) as unit_name")
                    ])
                    ->get()
                    ->map(function ($item) {
                        // কারেন্ট লাইভ স্টক = মোট কেনা - মোট বেচা
                        $item->current_quantity = $item->total_in - $item->total_out;
                        // কারেন্ট এসেট ভ্যালু = বর্তমান পরিমাণ * সর্বশেষ বাজার দর
                        $item->asset_value = $item->current_quantity * ($item->last_unit_price ?? 0);
                        return $item;
                    })
                    // শুধুমাত্র যে মালগুলো বর্তমানে স্টকে অবশিষ্টাংশ আছে (> 0) সেগুলোই প্রতিবেদনে দেখাবে
                    ->filter(fn ($item) => $item->current_quantity > 0);

                // মোট অবিক্রীত মালের রিয়েল-টাইম বাজার মূল্য
                $totalStockValue = $stockReports->sum('asset_value');

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