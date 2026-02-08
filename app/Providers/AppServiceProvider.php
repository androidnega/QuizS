<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view): void {
            if (request()->routeIs('admin.*')) {
                $view->with('staffPrefix', 'admin');
            } elseif (request()->routeIs('examiner.*')) {
                $view->with('staffPrefix', 'examiner');
            }
        });
    }
}
