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
                            ->live() // Forces instant lifecycle re-hydration
                            ->afterStateUpdated(fn ($set) => $set('category_id', null))
                            ->columnSpan(['default' => 12, 'md' => 4]),

                        Forms\Components\Select::make('category_id')
                            ->label('খাত / ক্যাটাগরি')
                            ->required()
                            ->searchable()
                            ->live()
                            // 🔥 THE PERFECT LOOKUP FIX: Maps relationship directly and filters reactively by the selected type
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyRuleQueryUsing: fn (Forms\Get $get, $query) => $query
                                    ->where('type', $get('type') ?? 'credit')
                            )
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('নতুন খাতের নাম')
                                    ->required(),
                            ])
                            // 🔥 THE COMPONENT SAVE FIX: Manually injects the selected type straight into creation
                            ->createOptionUsing(function (array $data, Forms\Get $get) {
                                $category = Category::create([
                                    'name' => $data['name'],
                                    'type' => $get('type') ?? 'credit',
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

                // 🔥 STOCKS SUB-FORM PANEL: Sliders open dynamically ONLY if the selected sector name contains "স্টক"
                Forms\Components\Section::make('স্টক / ইনভেন্টরি বিবরণী')
                    ->description('পণ্য ক্রয় বা বিক্রয়ের অতিরিক্ত তথ্য এখানে পূরণ করুন।')
                    ->visible(function (Forms\Get $get) {
                        $categoryId = $get('category_id');
                        if (!$categoryId) return false;
                        $category = Category::find($categoryId);
                        return $category && (str_contains($category->name, 'স্টক') || str_contains($category->name, 'স্টকের'));
                    })
                    ->schema([
                        Forms\Components\Select::make('stock_type_id')
                            ->label('স্টকের ধরণ (পণ্যের নাম)')
                            ->options(StockType::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
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
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('নতুন পরিমাপের একক')
                                    ->required(),
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
