<?php

namespace App\Providers;

use App\Support\Menu\AppMenuItem;
use App\Support\Menu\Facades\Menu;
use App\Support\Menu\MenuRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
       $this->app->singleton('app-menu', fn () => new MenuRegistry());
    }

    public function boot(): void
    {
        Menu::addItems([
            AppMenuItem::make('nav.home', '/cms/dashboard')
                ->icon('bi-house')->iconColor('#4f8bc8')->group('nav.main')->order(1),
            AppMenuItem::make('nav.myProfile', '/cms/profile')
                ->icon('bi-person')->iconColor('#5f77cf')->group('nav.main')->order(2),
        ]);
    }
}
