<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\User;
use App\Policies\CompanyPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
