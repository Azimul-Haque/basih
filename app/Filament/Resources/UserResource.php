<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'ব্যবহারকারী তালিকা';

    protected static ?int $navigationSort = 6;

    protected static ?string $pluralModelLabel = 'ব্যবহারকারী তালিকা';

    /**
     * 🔥 ম্যাজিক পার্ট: শুধুমাত্র আপনার মোবাইল নম্বর হলেই সাইডবার মেনু রেজিস্টার হবে
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        // ইউজার লগইন থাকা অবস্থা এবং তার মোবাইল নম্বর আপনার নম্বরের সাথে মিললে TRUE হবে
        return $user && $user->phone === '01751398392'; 
    }

    /**
     * 🔒 অতিরিক্ত নিরাপত্তা লেয়ার: কেউ যদি সরাসরি ইউআরএল (admin/users) টাইপ করেও ঢুকতে চায়, 
     * তাহলেও যেন সুপার এডমিন ছাড়া বাকিদের ৪0৩ ফরবিডেন বা ব্লক দেখায়।
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        
        return $user && $user->phone === '01751398392';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mobile')
                    ->required()
                    ->maxLength(11)
                    ->unique(table: 'users', column: 'mobile', ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'This mobile number is already registered in the system.',
                    ]),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->unique(table: 'users', column: 'email', ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'This email is already registered in the system.',
                    ]),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
