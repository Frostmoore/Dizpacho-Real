<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Chat\ChatServiceInterface;
use App\Services\Chat\MockChatService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ChatServiceInterface::class, 
            MockChatService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
