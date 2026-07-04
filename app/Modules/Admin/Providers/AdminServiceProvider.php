<?php

namespace App\Modules\Admin\Providers;

use App\Support\Menu\MenuRegistry;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $menu = $this->app->make(MenuRegistry::class);

        $menu->register('/cms/admin/users', 'Users', [
            'icon'      => 'bi-shield-lock',
            'iconColor' => '#5f77cf',
            'group'     => 'Admin',
            'roles'     => ['admin'],
            'position'  => 50,
        ]);

        $menu->register('/cms/admin/login-logs', 'Login Logs', [
            'icon'      => 'bi-clock-history',
            'iconColor' => '#b26464',
            'group'     => 'Admin',
            'roles'     => ['admin'],
            'position'  => 51,
        ]);

        $menu->register('/cms/admin/audit-logs', 'Audit Logs', [
            'icon'      => 'bi-journal-text',
            'iconColor' => '#7a6bc4',
            'group'     => 'Admin',
            'roles'     => ['admin'],
            'position'  => 52,
        ]);
    }
}