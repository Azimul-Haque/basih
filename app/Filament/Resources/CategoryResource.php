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
    protected static ?string $modelLabel = 'ক্যাটাগরি/খাত';
    protected static ?string $pluralModelLabel = 'জমা-খরচের ধরণ';

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
                            ->live() // Instantly reveals dependent inputs
                            ->columnSpan(['default' => 12, 'md' => 6]),
                            
                        // Dynamic Toggle: Only slides open if the type is explicitly set to Debit (খরচ)
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
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
