<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;

use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Category;
use App\Models\StockType;
use App\Models\Unit;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Navigation\NavigationItem;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-bangladeshi';
    protected static ?string $navigationLabel = 'লেনদেন খাতা';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'লেনদেন';
    protected static ?string $pluralModelLabel = 'লেনদেন খাতা';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Card Wrapper for clean spacing
                Forms\Components\Section::make('লেনদেনের মূল বিবরণ')
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('তারিখ')
                            ->default(now())
                            ->required()
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\ToggleButtons::make('type')
                            ->label('লেনদেনের ধরণ')
                            ->options([
                                'credit' => 'জমা (Credit)',
                                'debit' => 'খরচ (Debit)',
                            ])
                            ->colors([
                                'credit' => 'success',
                                'debit' => 'danger',
                            ])
                            ->inline()
                            ->required()
                            ->default('credit')
                            ->live()            
                            ->afterStateUpdated(fn ($set) => $set('category_id', null)) 
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\Select::make('category_id')
                            ->label('খাত / ক্যাটাগরি')
                            ->required()
                            ->searchable()
                            ->live() 
                            ->options(function (Forms\Get $get) {
                                $selectedType = $get('type') ?? 'credit';
                                
                                // --- খরচ (DEBIT) মোড ---
                                if ($selectedType === 'debit') {
                                    $debitCategories = Category::where('type', 'debit')->get();
                                    $debitOptions = [];
                                    foreach ($debitCategories as $cat) {
                                        $debitOptions[$cat->id] = $cat->is_stock ? $cat->name . ' [স্টক]' : $cat->name;
                                    }
                                    return $debitOptions;
                                }

                                // --- জমা (CREDIT) মোড ---
                                $standardCredits = Category::where('type', 'credit')->pluck('name', 'id')->toArray();
                                $stockDebits = Category::where('type', 'debit')->where('is_stock', true)->get();

                                $salesOptions = [];
                                foreach ($stockDebits as $cat) {
                                    // 📦 stock_items টেবিলের সাথে JOIN করে প্রকৃত মোট ক্রয় হিসাব (Debit)
                                    $totalPurchased = (float) \DB::table('transactions')
                                        ->join('stock_items', 'transactions.id', '=', 'stock_items.transaction_id')
                                        ->where('transactions.category_id', $cat->id)
                                        ->where('transactions.type', 'debit')
                                        ->sum('stock_items.quantity');

                                    // 📦 stock_items টেবিলের সাথে JOIN করে প্রকৃত মোট বিক্রয় হিসাব (Credit)
                                    $totalSold = (float) \DB::table('transactions')
                                        ->join('stock_items', 'transactions.id', '=', 'stock_items.transaction_id')
                                        ->where('transactions.category_id', $cat->id)
                                        ->where('transactions.type', 'credit')
                                        ->sum('stock_items.quantity');

                                    $currentStock = $totalPurchased - $totalSold;

                                    // 🔥 শর্ত: গুদামে মাল ০ থেকে বেশি থাকলেই কেবল বিক্রয়ের জন্য অপশনটি আসবে
                                    if ($currentStock > 0) {
                                        // সর্বশেষ এন্ট্রি করা এককটি খুঁজে বের করা
                                        $lastStockItem = \DB::table('transactions')
                                            ->join('stock_items', 'transactions.id', '=', 'stock_items.transaction_id')
                                            ->join('units', 'stock_items.unit_id', '=', 'units.id')
                                            ->where('transactions.category_id', $cat->id)
                                            ->select('units.name')
                                            ->latest('transactions.created_at')
                                            ->first();

                                        $unitLabel = $lastStockItem ? $lastStockItem->name : 'একক';

                                        $salesOptions[$cat->id] = $cat->name . ' - বিক্রয় (মজুদ: ' . number_format($currentStock) . ' ' . $unitLabel . ')';
                                    }
                                }

                                return $standardCredits + $salesOptions;
                            })
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('নতুন খাতের নাম')
                                    ->required()
                                    ->unique(
                                        table: 'categories',
                                        column: 'name',
                                        modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, Forms\Components\TextInput $component) {
                                            $livewireData = $component->getLivewire()->data;
                                            $parentType = $livewireData['type'] ?? 'credit';
                                            return $rule->where('type', $parentType);
                                        }
                                    ),

                                Forms\Components\Toggle::make('is_stock')
                                    ->label('এটি কি স্টকের খাত?')
                                    ->helperText('হ্যাঁ দিলে এই খাতে খরচ করার সময় পণ্যের পরিমাণ ও পরিমাপের একক (ইউনিট) এন্ট্রি করতে হবে।')
                                    ->default(false)
                                    ->visible(function (Forms\Components\Toggle $component) {
                                        $livewireData = $component->getLivewire()->data;
                                        return ($livewireData['type'] ?? 'credit') === 'debit';
                                    }),
                            ])
                            ->createOptionUsing(function (array $data, Forms\Components\Select $component) {
                                $livewireData = $component->getLivewire()->data;
                                $parentType = $livewireData['type'] ?? 'credit';

                                $category = Category::create([
                                    'name' => $data['name'],
                                    'type' => $parentType, 
                                    'is_stock' => $data['is_stock'] ?? false, 
                                ]);

                                return $category->id; 
                            })
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\TextInput::make('amount')
                            ->label('মোট টাকার অংক')
                            ->numeric()
                            ->prefix('৳')
                            ->required()
                            ->hint(function (Forms\Get $get, $record) {
                                if (($get('type') ?? 'credit') !== 'debit') return null;

                                // ১. মোট জমা (Credit) অ্যামাউন্ট
                                $totalCredit = \App\Models\Transaction::where('type', 'credit')->sum('amount');
                                
                                // ২. মোট সাধারণ খরচ (Debit Amount)
                                $totalDebitAmount = \App\Models\Transaction::where('type', 'debit')->sum('amount');
                                
                                // ৩. 📦 স্টক আইটেম টেবিল থেকে মোট অতিরিক্ত খরচ (Extra Cost)
                                $totalExtraCost = \DB::table('stock_items')->sum('extra_cost');

                                // ৪. প্রকৃত ব্যবহারযোগ্য ব্যালেন্স = মোট জমা - (মোট খরচ + মোট অতিরিক্ত খরচ)
                                $currentBalance = $totalCredit - ($totalDebitAmount + $totalExtraCost);

                                // এডিট পেজে থাকলে বর্তমান রেকর্ডের নিজের অ্যামাউন্টটুকু ব্যালেন্সে ব্যাকপাস করতে হবে যেন এটি ভ্যালিডেশনে আটকে না যায়
                                if ($record) {
                                    $currentBalance += (float) $record->amount;
                                }

                                return 'বর্তমান ব্যবহার্য ব্যালেন্স: ৳' . number_format($currentBalance);
                            })
                            ->hintColor('warning') 
                            ->rules(function (Forms\Get $get, $record) {
                                if (($get('type') ?? 'credit') === 'credit') return [];

                                $totalCredit = \App\Models\Transaction::where('type', 'credit')->sum('amount');
                                $totalDebitAmount = \App\Models\Transaction::where('type', 'debit')->sum('amount');
                                $totalExtraCost = \DB::table('stock_items')->sum('extra_cost');

                                $currentBalance = $totalCredit - ($totalDebitAmount + $totalExtraCost);

                                if ($record) {
                                    $currentBalance += (float) $record->amount;
                                }

                                return ['max:' . $currentBalance];
                            })
                            ->validationMessages([
                                'max' => 'আপনার ক্যাশে পর্যাপ্ত টাকা নেই! বর্তমান সর্বোচ্চ ব্যবহার্য ব্যালেন্স: ৳:max',
                            ])
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        Forms\Components\TextInput::make('note')
                            ->label('অতিরিক্ত বিবরণ / মন্তব্য')
                            ->columnSpan(['default' => 12, 'md' => 6]),
                    ])->columns(12),

                // 🔥 STOCKS SUB-FORM PANEL
                Forms\Components\Section::make(function (Forms\Get $get) {
                    // Step out to read root parameters dynamically
                    $livewireData = $get('../') ?? [];
                    $type = data_get($livewireData, 'type') ?? 'credit';
                    
                    return $type === 'debit' 
                        ? '📦 স্টক / ইনভেন্টরি বিবরণী (ক্রয় / মাল প্রাপ্তি)' 
                        : '📦 স্টক / ইনভেন্টরি বিবরণী (বিক্রয় / মাল খালাস)';
                })
                    ->description('পণ্য পরিমাণ ও একক সংক্রান্ত অতিরিক্ত তথ্য এখানে পূরণ করুন।')
                    ->relationship('stockItem') 
                    ->visible(function (Forms\Get $get) {
                        $categoryId = $get('category_id');
                        if (!$categoryId) return false;
                        
                        $category = \App\Models\Category::find($categoryId);
                        return $category && $category->is_stock;
                    })
                    ->schema([
                        // ১. পরিমাপের একক (ইউনিট)
                        // Forms\Components\Select::make('unit_id')
                        //     ->label('পরিমাপের একক (ইউনিট)')
                        //     ->options(\App\Models\Unit::pluck('name', 'id'))
                        //     ->searchable()
                        //     ->preload()
                        //     ->required()
                        //     // ১. ডাইনামিক ডিফল্ট ভ্যালু (আগের মতোই থাকবে)
                        //     ->default(function (Forms\Get $get) {
                        //         $categoryId = $get('../../category_id') ?? $get('../category_id');
                        //         if (!$categoryId) return null;

                        //         $lastStockItem = \DB::table('stock_items')
                        //             ->join('transactions', 'stock_items.transaction_id', '=', 'transactions.id')
                        //             ->where('transactions.category_id', $categoryId)
                        //             ->where('transactions.type', 'debit')
                        //             ->orderBy('transactions.date', 'desc')
                        //             ->orderBy('transactions.id', 'desc')
                        //             ->first();

                        //         return $lastStockItem ? $lastStockItem->unit_id : null;
                        //     })
                        //     // 🔥 ম্যাজিক পার্ট: ক্রেডিট বা বিক্রয় মোড হলে ফিল্ডটিতে ক্লিক বা এডিট করা যাবে না, 
                        //     // কিন্তু ভ্যালুটি ইনপুটে একদম স্পষ্ট ও সুন্দরভাবে ভেসে থাকবে!
                        //     ->extraAttributes(function (Forms\Get $get) {
                        //         $type = $get('../../type') ?? $get('../type') ?? request()->input('components.0.snapshot.data.data.type') ?? 'credit';
                                
                        //         if ($type === 'credit') {
                        //             return [
                        //                 'style' => 'pointer-events: none; background-color: rgba(243, 244, 246, 0.1); cursor: not-allowed;',
                        //                 'tabindex' => '-1', // কিবোর্ড ফোকাস ব্লক করার জন্য
                        //             ];
                        //         }
                                
                        //         return [];
                        //     })
                        //     ->createOptionForm([
                        //         Forms\Components\TextInput::make('name')->label('নতুন পরিমাপের একক (ইউনিট)')->required(),
                        //     ])
                        //     ->createOptionUsing(fn (array $data) => \App\Models\Unit::create($data)->id)
                        //     ->columnSpan(['default' => 12, 'md' => 6]),

                        // ১. বিক্রয় মোডে শুধু দেখার জন্য প্লেসহোল্ডার (আগের মতোই থাকবে)
                        Forms\Components\Placeholder::make('unit_name_placeholder')
                            ->label('পরিমাপের একক')
                            ->content(function (Forms\Get $get) {
                                $categoryId = $get('../../category_id') ?? $get('../category_id');
                                if (!$categoryId) return 'খাত নির্বাচন করুন...';

                                $lastStockItem = \DB::table('stock_items')
                                    ->join('transactions', 'stock_items.transaction_id', '=', 'transactions.id')
                                    ->where('transactions.category_id', $categoryId)
                                    ->where('transactions.type', 'debit')
                                    ->orderBy('transactions.date', 'desc')
                                    ->orderBy('transactions.id', 'desc')
                                    ->first();

                                return $lastStockItem ? (\App\Models\Unit::find($lastStockItem->unit_id)?->name ?? 'N/A') : 'কোনো একক পাওয়া যায়নি';
                            })
                            ->visible(fn (Forms\Get $get) => ($get('../../type') ?? $get('../type') ?? 'credit') === 'credit')
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        // ২. আসল ড্রপডাউন সিলেক্টর (শুধুমাত্র ক্রয় মোডে দৃশ্যমান ও ইনপুটযোগ্য)
                        Forms\Components\Select::make('unit_id')
                            ->label('পরিমাপের একক')
                            ->options(\App\Models\Unit::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (Forms\Get $get) => ($get('../../type') ?? $get('../type') ?? 'credit') === 'debit')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('নতুন পরিমাপের একক')->required(),
                            ])
                            ->createOptionUsing(fn (array $data) => \App\Models\Unit::create($data)->id)
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        // ২. মালের পরিমাণ (ডাইনামিক আপার লিমিট এবং লাইভ স্টক হিন্টসহ)
                        Forms\Components\TextInput::make('quantity')
                            ->label('মালের পরিমাণ')
                            ->numeric()
                            ->required()
                            ->hint(function (Forms\Get $get, $record) {
                                // লাইভ সেফ চেক: ধাপে ধাপে প্যারেন্ট স্টেট খোঁজা
                                $type = $get('../../type') ?? $get('../type') ?? request()->input('components.0.snapshot.data.data.type') ?? 'credit';
                                $categoryId = $get('../../category_id') ?? $get('../category_id') ?? request()->input('components.0.snapshot.data.data.category_id');

                                // 🔥 আইডি না থাকলে কুয়েরি রান না করে এখানেই ব্রেক করা হলো
                                if (!$categoryId) return null;

                                $totalPurchased = (float) \DB::table('transactions')
                                    ->join('stock_items', 'transactions.id', '=', 'stock_items.transaction_id')
                                    ->where('transactions.category_id', $categoryId)
                                    ->where('transactions.type', 'debit')
                                    ->sum('stock_items.quantity');

                                $totalSold = (float) \DB::table('transactions')
                                    ->join('stock_items', 'transactions.id', '=', 'stock_items.transaction_id')
                                    ->where('transactions.category_id', $categoryId)
                                    ->where('transactions.type', 'credit')
                                    ->sum('stock_items.quantity');
                                
                                $availableStock = $totalPurchased - $totalSold;

                                if ($record && $record->stockItem) {
                                    $availableStock += (float) $record->stockItem->quantity;
                                }

                                if ($type === 'credit') {
                                    return '⚠️ গুদাম/স্টকে অবশিষ্ট আছে: ' . number_format($availableStock);
                                }

                                return null;
                            })
                            ->hintColor('warning')
                            ->rules(function (Forms\Get $get, $record) {
                                $type = $get('../../type') ?? $get('../type') ?? 'credit';
                                $categoryId = $get('../../category_id') ?? $get('../category_id');

                                // 🔥 আইডি বা টাইপ ক্র্যাশ প্রুফ গার্ড
                                if ($type === 'debit' || !$categoryId) return [];

                                $totalPurchased = (float) \DB::table('transactions')
                                    ->join('stock_items', 'transactions.id', '=', 'stock_items.transaction_id')
                                    ->where('transactions.category_id', $categoryId)
                                    ->where('transactions.type', 'debit')
                                    ->sum('stock_items.quantity');

                                $totalSold = (float) \DB::table('transactions')
                                    ->join('stock_items', 'transactions.id', '=', 'stock_items.transaction_id')
                                    ->where('transactions.category_id', $categoryId)
                                    ->where('transactions.type', 'credit')
                                    ->sum('stock_items.quantity');
                                
                                $availableStock = $totalPurchased - $totalSold;

                                if ($record && $record->stockItem) {
                                    $availableStock += (float) $record->stockItem->quantity;
                                }

                                return ['max:' . $availableStock];
                            })
                            ->validationMessages([
                                'max' => 'গুদামে পর্যাপ্ত মাল নেই! আপনার সর্বোচ্চ বিক্রয়যোগ্য পরিমাণ: :max',
                            ])
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        // ৩. অতিরিক্ত খরচ (পরিবহন/লেবার) -> শুধুমাত্র Debit মোডে দেখাবে, Credit মোডে পুরোপুরি লুকানো থাকবে
                        Forms\Components\TextInput::make('extra_cost')
                            ->label('অতিরিক্ত খরচ (পরিবহন/লেবার)')
                            ->numeric()
                            ->prefix('৳')
                            ->default(0)
                            // 🔥 লাইভ সেফ মাল্টি-লেভেল কন্ডিশনাল লুকআপ
                            ->hidden(function (Forms\Get $get) {
                                $type = $get('../../type') ?? $get('../type') ?? request()->input('components.0.snapshot.data.data.type') ?? 'credit';
                                return $type === 'credit';
                            })
                            ->columnSpan(['default' => 12, 'md' => 6]),
                    ])->columns(12)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    // বাম পাশের ব্লক: তারিখ, খাতের নাম এবং স্টক বিবরণী একসাথে স্ট্যাকড
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('date')
                            ->date('d M, Y')
                            ->label('তারিখ')
                            ->color('gray')
                            ->size('sm'),

                        Tables\Columns\TextColumn::make('category.name')
                            ->label('খাত')
                            ->weight('bold')
                            ->searchable()
                            ->size('md'),

                        // 🔥 নতুন: যদি ক্যাটাগরি স্টকের হয়, তবেই মালের পরিমাণ ও একক লাইভ দেখাবে
                        Tables\Columns\TextColumn::make('stockItem.quantity')
                            ->label('স্টক বিবরণী')
                            ->formatStateUsing(function ($state, $record) {
                                // 🔥 $record null হলে বা ক্যাটাগরি না থাকলে ক্র্যাশ এড়াতে সেফটি চেক
                                if (!$record || !$record->category || !$record->category->is_stock || !$record->stockItem) {
                                    return null;
                                }
                                
                                $qty = number_format($record->stockItem->quantity);
                                $unit = $record->stockItem->unit ? $record->stockItem->unit->name : 'একক';
                                
                                // 💰 প্রতি এককের দাম (Unit Price) বের করা
                                $unitPrice = number_format($record->stockItem->unit_price, 2);
                                
                                // প্রধান টেক্সট: পরিমাণ এবং প্রতি এককের দর
                                $text = "📦 পরিমাণ: {$qty} {$unit} (দর: ৳{$unitPrice}/{$unit})";

                                // যদি ক্রয় (debit) মোড হয় এবং অতিরিক্ত খরচ থাকে, তবে সেটিও পাশে দেখাবে
                                if ($record->type === 'debit' && $record->stockItem->extra_cost > 0) {
                                    $extra = number_format($record->stockItem->extra_cost);
                                    $text .= " (+ ৳{$extra} গাড়ি/লেবার)";
                                }

                                return $text;
                            })
                            ->color('success')
                            ->size('xs')
                            ->visible(fn ($record) => $record && $record->category && $record->category->is_stock && $record->stockItem),
                    ]),
                    
                    // ডান পাশের ব্লক: ক্রেডিট/ডেবিট অনুযায়ী ডাইনামিক কালার অ্যামাউন্ট ব্যাজ
                    Tables\Columns\TextColumn::make('amount')
                        ->label('টাকার পরিমাণ')
                        ->money('BDT', divideBy: 1)
                        ->formatStateUsing(function ($state, $record) {
                            $amount = (float) $state;
                            
                            // 🔥 যদি খরচ (debit) এবং স্টক ট্রানজেকশন হয়, তবে লাইভ অতিরিক্ত খরচ যোগ করে মোট টাকা দেখাবে
                            if ($record->type === 'debit' && $record->category && $record->category->is_stock && $record->stockItem) {
                                $amount += (float) $record->stockItem->extra_cost;
                            }
                            
                            return $amount;
                        })
                        ->prefix(fn ($record) => $record->type === 'credit' ? '+ ৳' : '- ৳')
                        ->color(fn ($record) => $record->type === 'credit' ? 'success' : 'danger')
                        ->weight('bold')
                        ->alignEnd(),
                ]),
                
                // 🔥 ডাইনামিক কলাপসিবল প্যানেল: মন্তব্য থাকলেই কেবল ড্রপডাউন অ্যারো বাটন ও প্যানেল আসবে
                Tables\Columns\Layout\Panel::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('note')
                            ->prefix('মন্তব্য: ')
                            ->color('gray')
                            ->size('sm'),
                    ]),
                ])
                ->collapsible()
                // মন্তব্য ফাঁকা হলে কলাপসিবল মেকানিজম হাইড করে দেবে
                ->visible(fn ($record) => $record && !empty($record->note)),
            ])
            ->filters([
                // ডেট রেঞ্জ ফিল্টার (মাসিক বা নির্দিষ্ট মেয়াদের হিসাব দেখার জন্য)
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('শুরুর তারিখ'),
                        Forms\Components\DatePicker::make('until')->label('শেষের তারিখ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('date', '<=', $data['until']));
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::class,
            // 🔥 রুট স্ল্যাগসহ কাস্টম পেজ রেজিস্টার করা হলো
            'credits' => Pages\ManageCredits::route('/credits'), 
            'debits' => Pages\ManageDebits::route('/debits'),    
            'create' => Pages\CreateTransaction::class,
            'edit' => Pages\EditTransaction::class,
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            // মেনু ১: জমা খাতা
            NavigationItem::make('জমা খাতা')
                ->label('জমা খাতা')
                ->icon('heroicon-o-arrow-trending-up')
                ->url(static::getUrl('credits'))
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.transactions.credits'))
                ->sort(2), // ড্যাশবোর্ডের ঠিক নিচে অবস্থান করবে

            // মেনু ২: খরচ খাতা
            NavigationItem::make('খরচ খাতা')
                ->label('খরচ খাতা')
                ->icon('heroicon-o-arrow-trending-down')
                ->url(static::getUrl('debits'))
                ->isActiveWhen(fn () => request()->routeIs('filament.admin.resources.transactions.debits'))
                ->sort(3),
        ];
    }
}