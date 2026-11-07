<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(
            function ($user, string $token) {

                $frontendUrl = rtrim(
                    env('FRONTEND_URL', 'http://localhost:5173'),
                    '/'
                );

                return $frontendUrl . '/reset-password?token='
                    . urlencode($token)
                    . '&email='
                    . urlencode($user->getEmailForPasswordReset());
            }
        );
    }
}