<x-filament-widgets::widget>
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ url('admin/transactions/credits') }}" class="block p-4 transition duration-200 rounded-xl shadow-sm border-l-4 border-emerald-500 hover:scale-[1.02] dark:bg-gray-800" style="background-color: rgba(16, 185, 129, 0.08);">
                <div class="flex items-center justify-between">
                    <span class="text-base font-black text-emerald-600 dark:text-emerald-400">➡️ জমা খাতা</span>
                    <x-heroicon-o-arrow-up-right class="w-5 h-5 text-emerald-500" />
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 hidden sm:block">সকল জমার বিবরণী দেখুন</p>
            </a>

            <a href="{{ url('admin/transactions/debits') }}" class="block p-4 transition duration-200 rounded-xl shadow-sm border-l-4 border-rose-500 hover:scale-[1.02] dark:bg-gray-800" style="background-color: rgba(239, 68, 68, 0.08);">
                <div class="flex items-center justify-between">
                    <span class="text-base font-black text-rose-600 dark:text-rose-400">➡️ খরচ খাতা</span>
                    <x-heroicon-m-arrow-down-right class="w-5 h-5 text-rose-500" />
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 hidden sm:block">সকল খরচের বিবরণী দেখুন</p>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">বর্তমান ক্যাশ ব্যালেন্স</div>
                <div class="text-2xl font-bold mt-1 {{ $currentBalance >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    ৳ {{ number_format($currentBalance) }}
                </div>
                <div class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                    <x-heroicon-m-wallet class="w-3.5 h-3.5" /> মোট জমা: ৳{{ number_format($totalCredit) }}
                </div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">সর্বমোট খরচ (ডেবিট)</div>
                <div class="text-2xl font-bold mt-1 text-rose-600">
                    ৳ {{ number_format($finalTotalDebit) }}
                </div>
                <div class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                    <x-heroicon-m-arrow-trending-down class="w-3.5 h-3.5" /> মূল: ৳{{ number_format($totalDebitAmount) }} | অতিরিক্ত: ৳{{ number_format($totalExtraCost) }}
                </div>
            </div>

            <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">আজকের তারিখ</div>
                <div class="text-xl font-bold mt-1.5 text-blue-600 dark:text-blue-400">
                    {{ $todayDate }}
                </div>
                <div class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                    <x-heroicon-m-clock class="w-3.5 h-3.5" /> জমা: ৳{{ number_format($todayCredit) }} | খরচ: ৳{{ number_format($todayTotalDebit) }}
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>