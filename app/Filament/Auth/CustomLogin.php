<?php

namespace App\Filament\Auth; // 👈 আপনার ফোল্ডার অনুযায়ী একদম সঠিক নেমস্পেস

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin; // 👈 এটি ফিলামেন্ট ভেন্ডরের কোর লগইন ক্লাস

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

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('লগইন করুন')
            ->submit('authenticate');
    }
}