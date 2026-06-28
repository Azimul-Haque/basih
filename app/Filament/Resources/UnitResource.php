<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Filament\Resources\UnitResource\RelationManagers;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'পরিমাপের একক';
    protected static ?int $navigationSort = 5;
    protected static ?string $pluralModelLabel = 'স্টক ইউনিট তালিকা';
    protected static ?string $navigationLabel = 'স্টক ইউনিট তালিকা';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('একক বিবরণী')
                    ->description('স্টক পরিমাপের নতুন একক এখানে যুক্ত করুন।')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('এককের নাম')
                            ->placeholder('যেমন: বস্তা, কেজি, টন, মন, লিটার')
                            ->required()
                            ->unique(
                                table: 'units',
                                column: 'name',
                                ignoreRecord: true // এডিট করার সময় যাতে ইউনিক ইরোর না দেখায়
                            )
                            ->maxLength(255)
                            ->columnSpan(12),
                    ])->columns(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->label('ক্রমিক')
                    ->rowIndex() // 🔥 ডাটাবেজ আইডি বাদ দিয়ে ১ থেকে লাইভ রো সিরিয়াল নম্বর জেনারেট করবে
                    ->formatStateUsing(function ($state) {
                        if (!$state) return null;

                        // ইংরেজি সংখ্যাকে ডাইনামিক বাংলায় কনভার্ট করার ম্যাপিং
                        $en = ['0','1','2','3','4','5','6','7','8','9'];
                        $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
                        
                        return str_replace($en, $bn, $state);
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('এককের নাম')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('তৈরির তারিখ')
                    ->dateTime('d M, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // প্রয়োজন হলে এখানে ফিল্টার যুক্ত করা যাবে
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('সম্পাদনা'),
                Tables\Actions\DeleteAction::make()->label('মুছুন'),
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
