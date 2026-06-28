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
        // // ১. সমস্ত আয়ের খাতসমূহের টোটাল সামারি + ট্রানজেকশন ডিটেইলস
        // $categoryCredits = \App\Models\Category::where('type', 'credit')
        //     ->whereHas('transactions')
        //     ->get()
        //     ->map(function ($category) {
        //         // এই খাতের সমস্ত ট্রানজেকশন হিস্ট্রি (তারিখ ও অ্যামাউন্ট)
        //         $details = \App\Models\Transaction::where('category_id', $category->id)
        //             ->orderByDesc('date')
        //             ->orderByDesc('id')
        //             ->get(['date', 'amount'])
        //             ->map(function ($t) {
        //                 // তারিখ বাংলায় রূপান্তর
        //                 $dateStr = \Carbon\Carbon::parse($t->date)->format('d M, Y');
        //                 $en = ['0','1','2','3','4','5','6','7','8','9','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        //                 $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯','জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
        //                 $t->bangla_date = str_replace($en, $bn, $dateStr);
        //                 return $t;
        //             });

        //         return [
        //             'id' => $category->id,
        //             'name' => $category->name,
        //             'total_amount' => $details->sum('amount'),
        //             'details' => $details,
        //         ];
        //     })
        //     ->sortByDesc('total_amount');

        // ১. সমস্ত আয়ের খাতসমূহ (Credit) + নির্দিষ্ট শর্তযুক্ত স্টক বিক্রির খাত (Debit)
        $categoryCredits = \App\Models\Category::where(function ($query) {
                $query->where('type', 'credit')
                      ->orWhere(function ($q) {
                          // এমন ডেবিট ক্যাটাগরি যা স্টক বিক্রির ট্রানজেকশন ধারণ করে
                          $q->where('type', 'debit')
                            ->whereHas('transactions', function ($tr) {
                                $tr->whereHas('stockItem', function ($s) {
                                    $s->where('is_stock', true)->where('type', 'sell');
                                });
                            });
                      });
            })
            ->whereHas('transactions') // নিশ্চিত করা যে ক্যাটাগরিতে ট্রানজেকশন আছে
            ->get()
            ->map(function ($category) {
                // ট্রানজেকশন এবং স্টক আইটেম লোড করা
                $details = \App\Models\Transaction::with('stockItem')
                    ->where('category_id', $category->id)
                    ->orderByDesc('date')
                    ->get(['id', 'date', 'amount', 'stock_item_id']);

                // স্টক সেল হিসেবে ফিল্টার করা
                $details = $details->map(function ($t) {
                    $isStockSell = ($t->stockItem && $t->stockItem->is_stock && $t->stockItem->type === 'sell');
                    $t->is_stock = $isStockSell;

                    // তারিখ বাংলায় রূপান্তর
                    $dateStr = \Carbon\Carbon::parse($t->date)->format('d M, Y');
                    $en = ['0','1','2','3','4','5','6','7','8','9','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯','জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
                    $t->bangla_date = str_replace($en, $bn, $dateStr);
                    
                    return $t;
                });

                // যদি ডেবিট ক্যাটাগরি হয়, তবে শুধু সেল ট্রানজেকশনগুলো রাখুন, নাহলে সব রাখুন
                if ($category->type === 'debit') {
                    $details = $details->filter(function ($t) {
                        return $t->is_stock === true;
                    });
                }

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_amount' => $details->sum('amount'),
                    'details' => $details,
                ];
            })
            ->filter(function ($item) {
                // যাদের ট্রানজেকশন আছে তাদেরই দেখান
                return count($item['details']) > 0;
            })
            ->sortByDesc('total_amount');

        // ২. সমস্ত খরচের খাতসমূহের টোটাল সামারি + ট্রানজেকশন ডিটেইলস
        $categoryDebits = \App\Models\Category::where('type', 'debit')
            ->whereHas('transactions')
            ->get()
            ->map(function ($category) {
                // এই খাতের সমস্ত ট্রানজেকশন হিস্ট্রি
                $details = \App\Models\Transaction::where('category_id', $category->id)
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->get()
                    ->map(function ($t) {
                        // স্টক আইটেমের এক্সট্রা কস্ট থাকলে তা খরচে যোগ হবে
                        $amount = (float) $t->amount;
                        if ($t->category && $t->category->is_stock && $t->stockItem) {
                            $amount += (float) $t->stockItem->extra_cost;
                        }
                        $t->final_amount = $amount;

                        // তারিখ বাংলায় রূপান্তর
                        $dateStr = \Carbon\Carbon::parse($t->date)->format('d M, Y');
                        $en = ['0','1','2','3','4','5','6','7','8','9','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯','জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
                        $t->bangla_date = str_replace($en, $bn, $dateStr);
                        return $t;
                    });

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'total_amount' => $details->sum('final_amount'),
                    'details' => $details,
                ];
            })
            ->sortByDesc('total_amount');

        // গ্র্যান্ড টোটাল ক্যালকুলেশন
        $grandTotalCredit = $categoryCredits->sum('total_amount');
        $grandTotalDebit = $categoryDebits->sum('total_amount');

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