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
                
                <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                    @forelse($categoryCredits as $credit)
                        <div x-data="{ open: false }" class="border border-gray-50 dark:border-gray-700/50 rounded-xl overflow-hidden">
                            <div @click="open = !open" class="flex justify-between items-center text-sm p-3 bg-gray-50/50 dark:bg-gray-900/20 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/10 cursor-pointer transition duration-150">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90 text-emerald-500': open }" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300 font-bold">{{ $credit['name'] }}</span>
                                </div>
                                <span class="font-black text-emerald-600">৳{{ number_format($credit['total_amount'], 2) }}</span>
                            </div>

                            <div x-show="open" x-collapse x-cloak class="bg-white dark:bg-gray-900/40 px-3 py-1 border-t border-gray-50 dark:border-gray-800 divider-y divide-gray-100 dark:divide-gray-800">
                                @foreach($credit['details'] as $detail)
                                    <div class="flex justify-between items-center py-2 text-xs border-b border-gray-50 dark:border-gray-800/60 last:border-0">
                                        <span class="text-gray-400">{{ $detail->bangla_date }}</span>
                                        <span class="font-medium text-gray-600 dark:text-gray-400">৳{{ number_format($detail->amount, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
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
                        <h4 class="text-base font-bold text-gray-800 dark:text-gray-200">খরচের খাতসমূহ (সর্বমোট)</h4>
                    </div>
                    <span class="text-sm font-black text-rose-600 bg-rose-50 dark:bg-rose-950/30 px-2.5 py-1 rounded-xl">৳{{ number_format($grandTotalDebit, 2) }}</span>
                </div>
                
                <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                    @forelse($categoryDebits as $debit)
                        <div x-data="{ open: false }" class="border border-gray-50 dark:border-gray-700/50 rounded-xl overflow-hidden">
                            <div @click="open = !open" class="flex justify-between items-center text-sm p-3 bg-gray-50/50 dark:bg-gray-900/20 hover:bg-rose-50/30 dark:hover:bg-rose-950/10 cursor-pointer transition duration-150">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-90 text-rose-500': open }" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300 font-bold">{{ $debit['name'] }}</span>
                                </div>
                                <span class="font-black text-rose-600">৳{{ number_format($debit['total_amount'], 2) }}</span>
                            </div>

                            <div x-show="open" x-collapse x-cloak class="bg-white dark:bg-gray-900/40 px-3 py-1 border-t border-gray-50 dark:border-gray-800 divider-y divide-gray-100 dark:divide-gray-800">
                                @foreach($debit['details'] as $detail)
                                    <div class="flex justify-between items-center py-2 text-xs border-b border-gray-50 dark:border-gray-800/60 last:border-0">
                                        <span class="text-gray-400">{{ $detail->bangla_date }}</span>
                                        <span class="font-medium text-gray-600 dark:text-gray-400">৳{{ number_format($detail->final_amount, 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
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
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">বর্তমান গুদাম ও লাইভ স্টক ব্যালেন্স</h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400 block">স্টকে থাকা মালের মোট মূল্য</span>
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
                                <span class="text-xs text-gray-500 block">গুদামে আছে: <strong class="text-amber-600 font-bold text-sm">{{ number_format($stock->current_quantity) }}</strong> {{ $stock->unit_name ?? 'একক' }}</span>
                                <span class="text-[11px] text-gray-400 block">সর্বশেষ ক্রয়ের দর: ৳{{ number_format($stock->last_unit_price ?? 0, 2) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-400 block">বর্তমান স্টক ভ্যালু</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200 block">৳{{ number_format($stock->asset_value, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>