<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;

class CustomLogin extends BaseLogin
{
    public function getHeading(): string
    {
        return 'হিসাব সিস্টেমে প্রবেশ করুন';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('mobile') // 👈 আপনার ডাটাবেজের আসল কলাম
                    ->label('মোবাইল নম্বর')
                    ->placeholder('01711000000')
                    ->required()
                    ->autofocus()
                    ->extraInputAttributes(['inputmode' => 'numeric']),

                $this->getPasswordFormComponent()
                    ->label('পিন নম্বর')
                    ->placeholder('••••'),

                $this->getRememberFormComponent()
                    ->label('লগইন তথ্য মনে রাখুন'),
            ])
            ->statePath('data');
    }

    // 🔥 ল্যারাভেলকে বলা: 'mobile' কলাম দিয়ে ইউজার ভেরিফাই করো
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'mobile' => $data['mobile'], // 👈 পরিবর্তন এখানে
            'password' => $data['password'],
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('লগইন করুন')
            ->submit('authenticate');
    }
}