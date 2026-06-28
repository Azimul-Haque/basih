<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Actions\Action;

class CustomLogin extends BaseLogin
{
    // ১. লগইন পেজের মূল শিরোনাম
    public function getHeading(): string
    {
        return 'হিসাব সিস্টেমে প্রবেশ করুন';
    }

    // ২. ইনপুট ফিল্ডগুলোর বাংলা লেবেল ও প্লেসহোল্ডার
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()
                    ->label('ইমেইল এড্রেস')
                    ->placeholder('admin@hisab.com'),

                $this->getPasswordFormComponent()
                    ->label('পাসওয়ার্ড')
                    ->placeholder('••••••••'),

                $this->getRememberFormComponent()
                    ->label('লগইন তথ্য মনে রাখুন'),
            ])
            ->statePath('data');
    }

    // ৩. সাবমিট বাটনের বাংলা টেক্সট
    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('লগইন করুন')
            ->submit('authenticate');
    }
}