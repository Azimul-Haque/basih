<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class CustomLogin extends BaseLogin
{
    // CRITICAL: We override the internal property tracking so Livewire binds our data correctly
    public ?string $mobile = '';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getMobileFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            // We point the state path down cleanly to capture our array definitions
            ->statePath('data');
    }

    protected function getMobileFormComponent(): Component
    {
        return TextInput::make('mobile')
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
     * Overriding the data extractor so the framework maps our custom input 
     * straight into your database 'mobile' column.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'mobile'   => $data['mobile'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.mobile' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}