<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;

use App\Models\StockType;
use App\Models\Unit;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'জমা-খরচের ধরণ';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'ক্যাটাগরি/খাত';
    protected static ?string $pluralModelLabel = 'জমা-খরচের ধরণ';

    // Inside app/Filament/Resources/CategoryResource.php -> form() method:

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('খাত বা ক্যাটাগরির বিবরণ')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('খাতের নাম')
                            ->placeholder('যেমন: ক্যাশ, ব্যাংক লোন, যাতায়াত খরচ, ভুট্টা স্টক')
                            ->required()
                            // 🔥 THE UNIQUE RULE FOR STANDALONE MANAGER:
                            ->unique(
                                table: 'categories',
                                column: 'name',
                                modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, Forms\Get $get) {
                                    $selectedType = $get('type') ?? 'credit';
                                    return $rule->where('type', $selectedType);
                                },
                                ignoreRecord: true // <-- Filament v3 uses ignoreRecord chained or as a parameter here safely
                            )
                            ->columnSpan(['default' => 12, 'md' => 6]),

                        Forms\Components\ToggleButtons::make('type')
                            ->label('ধরণ')
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
                            ->columnSpan(['default' => 12, 'md' => 6]),
                            
                        Forms\Components\Toggle::make('is_stock')
                            ->label('এটি কি স্টকের খাত?')
                            ->helperText('হ্যাঁ দিলে এই খাতে খরচ করার সময় পণ্যের ধরণ ও একক এন্ট্রি করতে হবে।')
                            ->default(false)
                            ->live()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'debit')
                            ->columnSpan(12),
                    ])->columns(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('name')
                        ->label('খাতের নাম')
                        ->weight('bold')
                        ->searchable()
                        ->size('md'),
                        
                    Tables\Columns\TextColumn::make('type')
                        ->label('ধরণ')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => $state === 'credit' ? 'জমা (Credit)' : 'খরচ (Debit)')
                        ->color(fn (string $state): string => $state === 'credit' ? 'success' : 'danger')
                        ->alignEnd(),
                ]),
                
                // Displays a subtle sub-badge if the sector is flagged for inventory operations
                Tables\Columns\Layout\Panel::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('is_stock')
                            ->formatStateUsing(fn ($state) => $state ? '📦 স্টক ট্র্যাকিং সচল' : '')
                            ->color('warning')
                            ->size('sm'),
                    ]),
                ])->collapsible()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('ধরণ অনুযায়ী খুঁজুন')
                    ->options([
                        'credit' => 'জমা (Credit)',
                        'debit' => 'খরচ (Debit)',
                    ]),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
