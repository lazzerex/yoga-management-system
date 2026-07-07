<?php

namespace App\Modules\Admin\Providers;

use App\Support\Menu\MenuFacade as Menu;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Menu::register('/cms/admin/users', 'nav.users', [
            'icon'      => 'bi-shield-lock',
            'iconColor' => '#5f77cf',
            'group'     => 'nav.admin',
            'roles'     => ['admin'],
            'position'  => 50,
        ]);

        Menu::register('/cms/admin/login-logs', 'nav.loginLogs', [
            'icon'      => 'bi-clock-history',
            'iconColor' => '#b26464',
            'group'     => 'nav.admin',
            'roles'     => ['admin'],
            'position'  => 51,
        ]);

        Menu::register('/cms/admin/audit-logs', 'nav.auditLogs', [
            'icon'      => 'bi-journal-text',
            'iconColor' => '#7a6bc4',
            'group'     => 'nav.admin',
            'roles'     => ['admin'],
            'position'  => 52,
        ]);

        Menu::register('/cms/admin/form-demo', 'nav.formDemo', [
            'icon'      => 'bi-ui-checks',
            'iconColor' => '#3f8f6f',
            'group'     => 'nav.admin',
            'roles'     => ['admin'],
            'position'  => 53,
        ]);
    }
}
