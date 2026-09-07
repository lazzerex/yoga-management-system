<?php

namespace App\Providers;

use App\Support\Menu\AppMenuItem;
use App\Support\Menu\MenuRegistry;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Eventy;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bound by class so a type-hinted MenuRegistry and the Menu facade resolve
        // the same instance; 'app-menu' stays as the facade accessor.
        $this->app->singleton(MenuRegistry::class);
        $this->app->alias(MenuRegistry::class, 'app-menu');
    }

    public function boot(): void
    {
        // Seeded accounts sit on @yoga.local, so a live mailer without this
        // sends a burst of hard bounces at the provider.
        if (! $this->app->environment('production') && config('mail.always_to')) {
            Mail::alwaysTo(config('mail.always_to'));
        }

        // The core items register through the same hook the modules use, so the menu
        // is whatever register_backend_menu produces and never depends on who built
        // the registry. Priority 5 keeps them ahead of the modules.
        Eventy::addAction('register_backend_menu', function (MenuRegistry $menu) {
            $menu->addItems([
                AppMenuItem::make('nav.home', '/cms/dashboard')
                    ->icon('bi-house')->iconColor('#4f8bc8')->group('nav.main')->order(1),
                AppMenuItem::make('nav.myProfile', '/cms/profile')
                    ->icon('bi-person')->iconColor('#5f77cf')->group('nav.main')->order(2),
            ]);
        }, 5, 1);
    }
}
