<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login;

class MoodleLogin extends Login
{
    protected static string $view = 'filament.pages.moodle-login';

    public function mount(): void
    {
        // If already authenticated, redirect to admin
        if (auth()->guard('web')->check()) {
            redirect()->intended(filament()->getUrl());
        }
    }
}
