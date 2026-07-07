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
        Menu::register('/cms/dashboard', 'nav.home', [
            'icon'      => 'bi-house',
            'iconColor' => '#4f8bc8',
            'group'     => 'nav.main',
            'roles'     => [],
            'position'  => 1,
        ]);

        Menu::register('/cms/profile', 'nav.myProfile', [
            'icon'      => 'bi-person',
            'iconColor' => '#5f77cf',
            'group'     => 'nav.main',
            'roles'     => [],
            'position'  => 2,
        ]);

        // Operations group
        Menu::register('/cms/operations/yoga-center', 'nav.centers', [
            'icon'      => 'bi-building',
            'iconColor' => '#d99a34',
            'group'     => 'nav.operations',
            'roles'     => [],
            'position'  => 10,
        ]);

        Menu::register('/cms/operations/academy', 'nav.classes', [
            'icon'      => 'bi-people',
            'iconColor' => '#3fa07e',
            'group'     => 'nav.operations',
            'roles'     => [],
            'position'  => 11,
        ]);

        Menu::register('/cms/operations/teacher-attendance', 'nav.attendance', [
            'icon'      => 'bi-clipboard-check',
            'iconColor' => '#4f81cf',
            'group'     => 'nav.operations',
            'roles'     => ['admin', 'coach'],
            'position'  => 12,
        ]);

        Menu::register('/cms/operations/lesson-planning', 'nav.plans', [
            'icon'      => 'bi-calendar-check',
            'iconColor' => '#6a78c8',
            'group'     => 'nav.operations',
            'roles'     => ['admin', 'coach'],
            'position'  => 13,
            'badge'     => 'nav.approval',
        ]);

        Menu::register('/cms/operations/tuition-fees', 'nav.tuition', [
            'icon'      => 'bi-cash-stack',
            'iconColor' => '#32a06f',
            'group'     => 'nav.operations',
            'roles'     => ['admin', 'member'],
            'position'  => 14,
        ]);

        Menu::register('/cms/operations/file-library', 'nav.files', [
            'icon'      => 'bi-folder2-open',
            'iconColor' => '#c97846',
            'group'     => 'nav.operations',
            'roles'     => ['admin', 'coach'],
            'position'  => 15,
        ]);

        // Member group
        Menu::register('/cms/member/my-membership', 'nav.myMembership', [
            'icon'      => 'bi-card-checklist',
            'iconColor' => '#3f8f6f',
            'group'     => 'nav.member',
            'roles'     => ['member'],
            'position'  => 20,
        ]);

        Menu::register('/cms/member/my-classes', 'nav.myClasses', [
            'icon'      => 'bi-people',
            'iconColor' => '#3f7ec4',
            'group'     => 'nav.member',
            'roles'     => ['member'],
            'position'  => 21,
        ]);

        Menu::register('/cms/member/my-schedule', 'nav.mySchedule', [
            'icon'      => 'bi-calendar-check',
            'iconColor' => '#6a78c8',
            'group'     => 'nav.member',
            'roles'     => ['member'],
            'position'  => 22,
        ]);

        // Coach group
        Menu::register('/cms/coach/my-classes', 'nav.myClasses', [
            'icon'      => 'bi-people',
            'iconColor' => '#3f7ec4',
            'group'     => 'nav.coach',
            'roles'     => ['coach'],
            'position'  => 30,
        ]);

        Menu::register('/cms/coach/my-students', 'nav.myStudents', [
            'icon'      => 'bi-clipboard-check',
            'iconColor' => '#4f81cf',
            'group'     => 'nav.coach',
            'roles'     => ['coach'],
            'position'  => 31,
        ]);

        Menu::register('/cms/coach/my-teaching-schedule', 'nav.teachingSchedule', [
            'icon'      => 'bi-calendar-check',
            'iconColor' => '#6a78c8',
            'group'     => 'nav.coach',
            'roles'     => ['coach'],
            'position'  => 32,
        ]);
    }
}
