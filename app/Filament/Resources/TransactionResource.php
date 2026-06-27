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
                    return ($get('type') ?? 'credit') === 'debit' 
                        ? '📦 স্টক / ইনভেন্টরি বিবরণী (ক্রয় / মাল প্রাপ্তি)' 
                        : '📦 স্টক / ইনভেন্টরি বিবরণী (বিক্রয় / মাল খালাস)';
                })
                    ->description('পণ্য পরিমাণ ও একক সংক্রান্ত অতিরিক্ত তথ্য এখানে পূরণ করুন।')
                    ->visible(function (Forms\Get $get) {
                        $categoryId = $get('category_id');
                        if (!$categoryId) return false;
                        
                        $category = Category::find($categoryId);
                        return $category && $category->is_stock;
                    })
                    ->schema([
                        Forms\Components\Select::make('stockItem.unit_id') // <--- রিলেশনশিপ ডট নোটেশন
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

                        Forms\Components\TextInput::make('stockItem.quantity') // <--- রিলেশনশিপ ডট নোটেশন
                            ->label('মালের পরিমাণ')
                            ->numeric()
                            ->required()
                            ->rules(function (Forms\Get $get) {
                                if (($get('type') ?? 'credit') === 'debit') return [];

                                $categoryId = $get('category_id');
                                if (!$categoryId) return [];

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
                            ->visible(fn (Forms\Get $get) => $get('type') === 'debit') 
                            ->columnSpan(12),
                    ])->columns(12)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    // Left Block: Date & Category Name stacked together
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
                    ]),
                    
                    // Right Block: Amount Badge colored dynamically by Credit/Debit type
                    Tables\Columns\TextColumn::make('amount')
                        ->label('টাকার পরিমাণ')
                        ->money('BDT', divideBy: 1)
                        ->prefix(fn ($record) => $record->type === 'credit' ? '+ ৳' : '- ৳')
                        ->color(fn ($record) => $record->type === 'credit' ? 'success' : 'danger')
                        ->weight('bold')
                        ->alignEnd(),
                ]),
                
                // Collapsible panel visible under each row to display notes on mobile tapping
                Tables\Columns\Layout\Panel::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('note')
                            ->prefix('মন্তব্য: ')
                            ->color('gray')
                            ->visible(fn ($record) => !empty($record->note)),
                    ]),
                ])->collapsible(),
            ])
            ->filters([
                // Date Range Filter: Essential for tracking monthly summaries
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