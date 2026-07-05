<?php

namespace App\Modules\Admin\Providers;

use App\Support\Menu\MenuFacade as Menu;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Menu::register('/cms/admin/users', 'Users', [
            'icon'      => 'bi-shield-lock',
            'iconColor' => '#5f77cf',
            'group'     => 'Admin',
            'roles'     => ['admin'],
            'position'  => 50,
        ]);

        Menu::register('/cms/admin/login-logs', 'Login Logs', [
            'icon'      => 'bi-clock-history',
            'iconColor' => '#b26464',
            'group'     => 'Admin',
            'roles'     => ['admin'],
            'position'  => 51,
        ]);

        Menu::register('/cms/admin/audit-logs', 'Audit Logs', [
            'icon'      => 'bi-journal-text',
            'iconColor' => '#7a6bc4',
            'group'     => 'Admin',
            'roles'     => ['admin'],
            'position'  => 52,
        ]);

        Menu::register('/cms/admin/form-demo', 'Form Demo', [
            'icon'      => 'bi-ui-checks',
            'iconColor' => '#3f8f6f',
            'group'     => 'Admin',
            'roles'     => ['admin'],
            'position'  => 53,
        ]);
    }
}