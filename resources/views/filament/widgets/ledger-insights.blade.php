<x-filament-widgets::widget>
    <div class="space-y-6">
        
        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-600">📦</span>
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">লাইভ স্টক ও মালপত্র এনালিটিক্স</h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400 block">মোট মালের মূল্য</span>
                    <span class="text-sm font-black text-amber-600">৳{{ number_format($totalStockValue) }}</span>
                </div>
            </div>

            @if($stockReports->isEmpty())
                <p class="text-xs text-gray-400 text-center py-2">এই মুহূর্তে গুদামে কোনো মাল অবশিষ্ট নেই।</p>
            @else
                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    @foreach($stockReports as $stock)
                        <div class="flex items-center justify-between p-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl text-xs">
                            <div>
                                <span class="font-bold text-gray-700 dark:text-gray-300 block">{{ $stock->category_name }}</span>
                                <span class="text-gray-400">পরিমাণ: {{ number_format($stock->quantity) }} {{ $stock->unit_name ?? 'একক' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-500 block">মূল্য: ৳{{ number_format($stock->asset_value) }}</span>
                                @if($stock->quantity <= 5)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400 animate-pulse">⚠️ রি-অর্ডার করুন</span>
                                @else
                                    <span class="text-[10px] text-emerald-500 font-medium">✅ স্টক পর্যাপ্ত</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-3 border-b border-gray-50 dark:border-gray-700 pb-2">
                    <span class="text-emerald-500 text-sm">📈</span>
                    <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200">প্রধান আয়ের খাতসমূহ (টপ ৪)</h4>
                </div>
                <div class="space-y-2">
                    @forelse($categoryCredits as $credit)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-600 dark:text-gray-400">{{ $credit->name }}</span>
                            <span class="font-bold text-emerald-600">+ ৳{{ number_format($credit->total_amount) }}</span>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-400 py-1">কোনো জমার ডাটা নেই</p>
                    @endforelse
                </div>
            </div>

            <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-3 border-b border-gray-50 dark:border-gray-700 pb-2">
                    <span class="text-rose-500 text-sm">📉</span>
                    <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200">প্রধান খরচের খাতসমূহ (টপ ৪)</h4>
                </div>
                <div class="space-y-2">
                    @forelse($categoryDebits as $debit)
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-gray-600 dark:text-gray-400">{{ $debit->name }}</span>
                            <span class="font-bold text-rose-600">- ৳{{ number_format($debit->total_amount) }}</span>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-400 py-1">কোনো খরচের ডাটা নেই</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</x-filament-widgets::widget>