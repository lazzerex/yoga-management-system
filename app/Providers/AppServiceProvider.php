<?php

namespace App\Providers;

use App\Support\Menu\MenuFacade as Menu;
use App\Support\Menu\MenuRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MenuRegistry::class);

        class_alias(Menu::class, 'Menu');
    }

    public function boot(): void
    {
        // Main group (all roles)
        Menu::register('/cms/dashboard', 'Home', [
            'icon'      => 'bi-house',
            'iconColor' => '#4f8bc8',
            'group'     => 'Main',
            'roles'     => [],
            'position'  => 1,
        ]);

        Menu::register('/cms/profile', 'My Profile', [
            'icon'      => 'bi-person',
            'iconColor' => '#5f77cf',
            'group'     => 'Main',
            'roles'     => [],
            'position'  => 2,
        ]);

        // Operations group
        Menu::register('/cms/operations/yoga-center', 'Centers', [
            'icon'      => 'bi-building',
            'iconColor' => '#d99a34',
            'group'     => 'Operations',
            'roles'     => [],
            'position'  => 10,
        ]);

        Menu::register('/cms/operations/academy', 'Classes', [
            'icon'      => 'bi-people',
            'iconColor' => '#3fa07e',
            'group'     => 'Operations',
            'roles'     => [],
            'position'  => 11,
        ]);

        Menu::register('/cms/operations/teacher-attendance', 'Attendance', [
            'icon'      => 'bi-clipboard-check',
            'iconColor' => '#4f81cf',
            'group'     => 'Operations',
            'roles'     => ['admin', 'coach'],
            'position'  => 12,
        ]);

        Menu::register('/cms/operations/lesson-planning', 'Plans', [
            'icon'      => 'bi-calendar-check',
            'iconColor' => '#6a78c8',
            'group'     => 'Operations',
            'roles'     => ['admin', 'coach'],
            'position'  => 13,
            'badge'     => 'Approval',
        ]);

        Menu::register('/cms/operations/tuition-fees', 'Tuition', [
            'icon'      => 'bi-cash-stack',
            'iconColor' => '#32a06f',
            'group'     => 'Operations',
            'roles'     => ['admin', 'member'],
            'position'  => 14,
        ]);

        Menu::register('/cms/operations/file-library', 'Files', [
            'icon'      => 'bi-folder2-open',
            'iconColor' => '#c97846',
            'group'     => 'Operations',
            'roles'     => ['admin', 'coach'],
            'position'  => 15,
        ]);

        // Member group
        Menu::register('/cms/member/my-membership', 'My Membership', [
            'icon'      => 'bi-card-checklist',
            'iconColor' => '#3f8f6f',
            'group'     => 'Member',
            'roles'     => ['member'],
            'position'  => 20,
        ]);

        Menu::register('/cms/member/my-classes', 'My Classes', [
            'icon'      => 'bi-people',
            'iconColor' => '#3f7ec4',
            'group'     => 'Member',
            'roles'     => ['member'],
            'position'  => 21,
        ]);

        Menu::register('/cms/member/my-schedule', 'My Schedule', [
            'icon'      => 'bi-calendar-check',
            'iconColor' => '#6a78c8',
            'group'     => 'Member',
            'roles'     => ['member'],
            'position'  => 22,
        ]);

        // Coach group
        Menu::register('/cms/coach/my-classes', 'My Classes', [
            'icon'      => 'bi-people',
            'iconColor' => '#3f7ec4',
            'group'     => 'Coach',
            'roles'     => ['coach'],
            'position'  => 30,
        ]);

        Menu::register('/cms/coach/my-students', 'My Students', [
            'icon'      => 'bi-clipboard-check',
            'iconColor' => '#4f81cf',
            'group'     => 'Coach',
            'roles'     => ['coach'],
            'position'  => 31,
        ]);

        Menu::register('/cms/coach/my-teaching-schedule', 'Teaching Schedule', [
            'icon'      => 'bi-calendar-check',
            'iconColor' => '#6a78c8',
            'group'     => 'Coach',
            'roles'     => ['coach'],
            'position'  => 32,
        ]);
    }
}
