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
                            ->default('credit') // Auto-selects "জমা" on initial form render
                            ->live()            // Re-triggers form lifecycle evaluation on touch
                            ->afterStateUpdated(fn ($set) => $set('category_id', null)) // Resets choice on toggle
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\Select::make('category_id')
                            ->label('খাত / ক্যাটাগরি')
                            ->required()
                            ->searchable()
                            ->live() // Keep tracking component context mutations 
                            // The secure evaluation array pluck engine
                            ->options(function (Forms\Get $get) {
                                $selectedType = $get('type') ?? 'credit';
                                
                                return Category::where('type', $selectedType)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            // 🔥 POPUP MODAL RETURN REALTIME INJECTION
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('নতুন খাতের নাম')
                                    ->required()
                                    ->unique(
                                        table: 'categories',
                                        column: 'name',
                                        modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, mixed $component) {
                                            // Safe dynamic layout fallback lookup
                                            $mainFormState = $component->getContainer()->getParentComponent()->getLivewire()->data;
                                            $parentType = $mainFormState['type'] ?? 'credit';
                                            return $rule->where('type', $parentType);
                                        }
                                    ),

                                Forms\Components\Toggle::make('is_stock')
                                    ->label('এটি কি স্টকের খাত?')
                                    ->helperText('হ্যাঁ দিলে এই খাতে খরচ করার সময় পণ্যের ধরণ ও একক এন্ট্রি করতে হবে।')
                                    ->default(false)
                                    // 🔥 FIXED TYPE-HINT HERE: Using mixed to accept the Toggle component instance safely
                                    ->visible(function (Filament\Forms\Form $form) {
                                        $mainFormState = $form->getLivewire()->data;
                                        return ($mainFormState['type'] ?? 'credit') === 'debit';
                                    }),
                            ])
                            ->createOptionUsing(function (array $data, mixed $component) {
                                $mainFormState = $component->getContainer()->getParentComponent()->getLivewire()->data;
                                $parentType = $mainFormState['type'] ?? 'credit';

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
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        Forms\Components\TextInput::make('note')
                            ->label('অতিরিক্ত বিবরণ / মন্তব্য')
                            ->columnSpan(['default' => 12, 'md' => 6]),
                    ])->columns(12),

                // 🔥 STOCKS SUB-FORM PANEL: Sliders open dynamically ONLY if the selected sector has "is_stock" enabled in the database
                Forms\Components\Section::make('স্টক / ইনভেন্টরি বিবরণী')
                    ->description('পণ্য ক্রয় বা বিক্রয়ের অতিরিক্ত তথ্য এখানে পূরণ করুন।')
                    ->visible(function (Forms\Get $get) {
                        $categoryId = $get('category_id');
                        if (!$categoryId) return false;
                        
                        // Look up the actual category model directly
                        $category = Category::find($categoryId);
                        return $category && $category->is_stock;
                    })
                    ->schema([
                        Forms\Components\Select::make('stock_type_id')
                            ->label('স্টকের ধরণ (পণ্যের নাম)')
                            ->options(StockType::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            // 🔥 ADD ON THE GO FOR COMMODITY NAME
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('নতুন পণ্যের নাম')->required(),
                            ])
                            ->createOptionUsing(fn (array $data) => StockType::create($data)->id)
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\Select::make('unit_id')
                            ->label('পরিমাপের একক')
                            ->options(Unit::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            // 🔥 ADD ON THE GO FOR MEASUREMENT UNITS
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('নতুন পরিমাপের একক')->required(),
                            ])
                            ->createOptionUsing(fn (array $data) => Unit::create($data)->id)
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\TextInput::make('quantity')
                            ->label('মালের পরিমাণ')
                            ->numeric()
                            ->required()
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\TextInput::make('extra_cost')
                            ->label('অতিরিক্ত খরচ (পরিবহন/লেবার)')
                            ->numeric()
                            ->prefix('৳')
                            ->default(0)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'debit') // Only show extra cost on buying
                            ->columnSpan(['default' => 12, 'md' => 12]),
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