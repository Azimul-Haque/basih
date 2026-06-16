<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseAuth;
use Illuminate\Validation\ValidationException;

class Login extends BaseAuth
{
    /**
     * Overriding the root form layout map.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getLoginFormComponent(), // Swaps out default email
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * Building our explicit 11-digit mobile component structure natively.
     */
    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Mobile Number')
            ->tel()
            ->placeholder('01XXXXXXXXX')
            ->required()
            ->minLength(11)
            ->maxLength(11)
            ->regex('/^01[3-9]\d{8}$/')
            ->extraInputAttributes(['tabindex' => 1])
            ->autofocus();
    }

    /**
     * Maps the 'login' field input values directly into 
     * your database's 'mobile' column check.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'mobile'   => $data['login'],
            'password' => $data['password'],
        ];
    }

    /**
     * Redirect errors safely back into the custom field wire.
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}