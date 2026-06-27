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

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-bangladeshi';
    protected static ?string $navigationLabel = 'লেনদেন খাতা';
    protected static ?string $modelLabel = 'লেনদেন';
    protected static ?string $pluralModelLabel = 'লেনদেন খাতা';

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
                                    ->helperText('হ্যাঁ দিলে এই খাতে খরচ করার সময় পণ্যের পরিমাণ ও পরিমাপের একক এন্ট্রি করতে হবে।')
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
                            ->hint(function (Forms\Get $get) {
                                if (($get('type') ?? 'credit') !== 'debit') return null;

                                $totalCredit = \App\Models\Transaction::where('type', 'credit')->sum('amount');
                                $totalDebit = \App\Models\Transaction::where('type', 'debit')->sum('amount');
                                return 'বর্তমান সর্বোচ্চ ব্যালেন্স: ৳' . number_format($totalCredit - $totalDebit);
                            })
                            ->hintColor('warning') 
                            ->rules(function (Forms\Get $get) {
                                if (($get('type') ?? 'credit') === 'credit') return [];

                                $totalCredit = \App\Models\Transaction::where('type', 'credit')->sum('amount');
                                $totalDebit = \App\Models\Transaction::where('type', 'debit')->sum('amount');
                                return ['max:' . ($totalCredit - $totalDebit)];
                            })
                            ->validationMessages([
                                'max' => 'আপনার ক্যাশে পর্যাপ্ত টাকা নেই! বর্তমান সর্বোচ্চ ব্যালেন্স: ৳:max',
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
                    // 🔥 এই লাইনটি ফিলামেন্টকে বলবে স্টক রিলেশনের ডাটা এডিট ফর্মে অটো-লোড করতে
                    ->relationship('stockItem') 
                    ->visible(function (Forms\Get $get) {
                        $categoryId = $get('category_id');
                        if (!$categoryId) return false;
                        
                        $category = Category::find($categoryId);
                        return $category && $category->is_stock;
                    })
                    ->schema([
                        Forms\Components\Select::make('unit_id') // <--- ডট নোটেশন ছাড়া সাধারণ নাম
                            ->label('পরিমাপের একক')
                            ->options(\App\Models\Unit::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('নতুন পরিমাপের একক')->required(),
                            ])
                            ->createOptionUsing(fn (array $data) => \App\Models\Unit::create($data)->id)
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        Forms\Components\TextInput::make('quantity') // <--- ডট নোটেশন ছাড়া সাধারণ নাম
                            ->label('মালের পরিমাণ')
                            ->numeric()
                            ->required()
                            ->rules(function (Forms\Get $get) {
                                // যেহেতু সেকশনটি রিলেশনের ভেতরে ঢুকে গেছে, তাই টাইপ বা ক্যাটাগরি আইডি পেতে হলে 
                                // লাইভওয়্যারের প্যারেন্ট ডাটা এরে চেক করতে হবে
                                $livewireData = $get('../../') ?? []; 
                                $type = data_get($livewireData, 'type') ?? 'credit';
                                $categoryId = data_get($livewireData, 'category_id');

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
                                
                                $maxQuantity = $totalPurchased - $totalSold;

                                return ['max:' . $maxQuantity];
                            })
                            ->validationMessages([
                                'max' => 'গুদামে পর্যাপ্ত মাল নেই! সর্বোচ্চ বিক্রয়যোগ্য পরিমাণ: :max',
                            ])
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        Forms\Components\TextInput::make('extra_cost')
                            ->label('অতিরিক্ত খরচ (পরিবহন/লেবার)')
                            ->numeric()
                            ->prefix('৳')
                            ->default(0)
                            // 🔥 FIXED: Looks at the top-level absolute form layout context to verify type
                            ->visible(function (Forms\Get $get) {
                                // Step out twice from the nested relationship tree container state structure
                                $livewireData = $get('../../') ?? [];
                                $type = data_get($livewireData, 'type') ?? request()->input('components.0.snapshot.data.data.type') ?? 'credit';
                                
                                return $type === 'debit';
                            })
                            ->columnSpan(12),
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
                            ->formatStateUsing(function ($state, $record) {
                                // 🔥 $record null হলে বা ক্যাটাগরি না থাকলে ক্র্যাশ এড়াতে সেফটি চেক
                                if (!$record || !$record->category || !$record->category->is_stock || !$record->stockItem) {
                                    return null;
                                }
                                
                                $qty = number_format($record->stockItem->quantity);
                                $unit = $record->stockItem->unit ? $record->stockItem->unit->name : 'একক';
                                $text = "📦 পরিমাণ: {$qty} {$unit}";

                                if ($record->type === 'debit' && $record->stockItem->extra_cost > 0) {
                                    $extra = number_format($record->stockItem->extra_cost);
                                    $text .= " (+ ৳{$extra} গাড়ি/লেবার)";
                                }

                                return $text;
                            })
                            ->color('success')
                            ->size('xs')
                            // 🔥 FIXED: $record && চেক যুক্ত করা হয়েছে যেন null প্রোপার্টি এরর না আসে
                            ->visible(fn ($record) => $record && $record->category && $record->category->is_stock && $record->stockItem),
                    ]),
                    
                    // ডান পাশের ব্লক: ক্রেডিট/ডেবিট অনুযায়ী ডাইনামিক কালার অ্যামাউন্ট ব্যাজ
                    Tables\Columns\TextColumn::make('amount')
                        ->label('টাকার পরিমাণ')
                        ->money('BDT', divideBy: 1)
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}