<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $layout = 'filament.layouts.admin-login';

    protected static string $view = 'filament.pages.auth.login';

    public string $activeLoginTab = 'mobile';

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('نام کاربری')
            ->placeholder('ایمیل یا شماره موبایل')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes([
                'tabindex' => 1,
                'id' => 'usernameInput',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('رمز عبور')
            ->placeholder('رمز عبور خود را وارد کنید')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes([
                'tabindex' => 2,
                'id' => 'passwordInput',
            ]);
    }

    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()
            ->extraAttributes(['class' => 'admin-login-remember']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim((string) ($data['email'] ?? ''));

        if (str_contains($login, '@')) {
            return [
                'email' => $login,
                'password' => $data['password'],
            ];
        }

        return [
            'phone' => $login,
            'password' => $data['password'],
        ];
    }

    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        $this->activeLoginTab = 'username';

        return parent::authenticate();
    }

    protected function throwFailureValidationException(): never
    {
        $this->activeLoginTab = 'username';

        throw ValidationException::withMessages([
            'data.email' => 'نام کاربری یا رمز عبور اشتباه است.',
        ]);
    }

    public function getTitle(): string
    {
        return 'ورود';
    }

    public function getHeading(): string
    {
        return '';
    }
}
