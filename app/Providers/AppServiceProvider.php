<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ChatService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar ChatService como singleton (sin dependencias de IA)
        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
            if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
    }
}
