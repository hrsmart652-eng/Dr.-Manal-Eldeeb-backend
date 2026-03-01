<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        // Passport routes
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Token scopes (if needed)
        Passport::tokensCan([
            'student' => 'Student access',
            'instructor' => 'Instructor access',
            'admin' => 'Admin access',
        ]);

        Passport::setDefaultScope([
            'student',
        ]);
    }
}