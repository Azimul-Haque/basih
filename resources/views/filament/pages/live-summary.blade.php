<x-filament-panels::page>
    <div class="space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-500 text-lg">📈</span>
                        <h4 class="text-base font-bold text-gray-800 dark:text-gray-200">আয়ের খাতসমূহ (সর্বমোট)</h4>
                    </div>
                    <span class="text-sm font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30 px-2.5 py-1 rounded-xl">৳{{ number_format($grandTotalCredit, 2) }}</span>
                </div>
                
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    @forelse($categoryCredits as $credit)
                        <div class="flex justify-between items-center text-sm p-1.5 hover:bg-gray-50 dark:hover:bg-gray-900/30 rounded-lg">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">{{ $credit->name }}</span>
                            <span class="font-bold text-emerald-600">+ ৳{{ number_format($credit->total_amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-4 text-center">কোনো জমার ডাটা পাওয়া যায়নি</p>
                    @endforelse
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-rose-500 text-lg">📉</span>
                        <h4 class="text-base font-bold text-gray-800 dark:text-gray-200">খরচ/বিনিয়োগের খাতসমূহ (সর্বমোট)</h4>
                    </div>
                    <span class="text-sm font-black text-rose-600 bg-rose-50 dark:bg-rose-950/30 px-2.5 py-1 rounded-xl">৳{{ number_format($grandTotalDebit, 2) }}</span>
                </div>
                
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    @forelse($categoryDebits as $debit)
                        <div class="flex justify-between items-center text-sm p-1.5 hover:bg-gray-50 dark:hover:bg-gray-900/30 rounded-lg">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">{{ $debit->name }}</span>
                            <span class="font-bold text-rose-600">- ৳{{ number_format($debit->total_amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-4 text-center">কোনো খরচের ডাটা পাওয়া যায়নি</p>
                    @endforelse
                </div>
            </div>

        </div>

        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-600 text-lg">📦</span>
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">বিদ্যমান মালপত্র ও লাইভ স্টক এনালাইসিস</h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400 block">স্টকে থাকা মোট মালের আনুমানিক মূল্য</span>
                    <span class="text-base font-black text-amber-600">৳{{ number_format($totalStockValue, 2) }}</span>
                </div>
            </div>

            @if($stockReports->isEmpty())
                <div class="text-center py-6">
                    <span class="text-3xl">📭</span>
                    <p class="text-xs text-gray-400 mt-2">এই মুহূর্তে গুদামে কোনো মাল অবশিষ্ট নেই।</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($stockReports as $stock)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-100/50 dark:border-gray-700/50">
                            <div class="space-y-1">
                                <span class="font-bold text-sm text-gray-800 dark:text-gray-200 block">{{ $stock->category_name }}</span>
                                <span class="text-xs text-gray-500 block">অবশিষ্ট: <strong class="text-gray-700 dark:text-gray-300">{{ number_format($stock->quantity) }}</strong> {{ $stock->unit_name ?? 'একক' }}</span>
                                <span class="text-[11px] text-gray-400 block">একক দর: ৳{{ number_format($stock->unit_price, 2) }}</span>
                            </div>
                            <div class="text-right space-y-2">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 block">৳{{ number_format($stock->asset_value, 2) }}</span>
                                
                                @if($stock->quantity <= 5)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 animate-pulse border border-rose-100 dark:border-rose-900">⚠️ রি-অর্ডার</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">✅ পর্যাপ্ত</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>