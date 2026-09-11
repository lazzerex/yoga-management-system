<?php

namespace App\Modules\Admin\Providers;

use App\Support\Menu\AppMenuItem;
use App\Support\Menu\MenuRegistry;
use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Eventy;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {

        Eventy::addFilter('backend_settings_menu', function (array $items) {
            $items[] = AppMenuItem::make('nav.settingsSystem')
                ->nolink()
                ->permissions('admin.settings.system.view')
                ->addItems([
                    AppMenuItem::make('nav.settingsSystemGeneral', '/cms/admin/settings/system/general')
                        ->permissions('admin.settings.system.view'),
                    AppMenuItem::make('nav.settingsSystemAdvanced', '/cms/admin/settings/system/advanced')
                        ->permissions('admin.settings.system.view'),
                ]);

            return $items;
        }, 20, 1);

        Eventy::addAction('register_backend_menu', function (MenuRegistry $menu) {
            $settingsChildren = Eventy::filter('backend_settings_menu', [
                AppMenuItem::make('nav.settingsGeneral', '/cms/admin/settings/general')
                    ->permissions('admin.settings.view'),
            ]);

            $menu->addItems([
                AppMenuItem::make('nav.users', '/cms/admin/users')
                    ->icon('bi-shield-lock')->iconColor('#5f77cf')->group('nav.admin')->order(50)
                    ->permissions('admin.users.view'),
                AppMenuItem::make('nav.loginLogs', '/cms/admin/login-logs')
                    ->icon('bi-clock-history')->iconColor('#b26464')->group('nav.admin')->order(51)
                    ->permissions('admin.login-logs.view'),
                AppMenuItem::make('nav.auditLogs', '/cms/admin/audit-logs')
                    ->icon('bi-journal-text')->iconColor('#7a6bc4')->group('nav.admin')->order(52)
                    ->permissions('admin.audit-logs.view'),

                // Submenu example with mock children, per task allowance.
                AppMenuItem::make('nav.settings')
                    ->nolink()
                    ->icon('bi-gear')->iconColor('#6e7891')->group('nav.admin')->order(60)
                    ->permissions('admin.settings.view')
                    ->addItems($settingsChildren),
            ]);
        }, 20, 1);
    }
}