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
            // Main group (all roles)
            AppMenuItem::make('nav.home', '/cms/dashboard')
                ->icon('bi-house')->iconColor('#4f8bc8')->group('nav.main')->order(1),
            AppMenuItem::make('nav.myProfile', '/cms/profile')
                ->icon('bi-person')->iconColor('#5f77cf')->group('nav.main')->order(2),

            // Operations group
            AppMenuItem::make('nav.centers', '/cms/operations/yoga-center')
                ->icon('bi-building')->iconColor('#d99a34')->group('nav.operations')->order(10)
                ->permissions('operations.center.view'),
            AppMenuItem::make('nav.classes', '/cms/operations/academy')
                ->icon('bi-people')->iconColor('#3fa07e')->group('nav.operations')->order(11)
                ->permissions('operations.sessions.view'),
            AppMenuItem::make('nav.attendance', '/cms/operations/teacher-attendance')
                ->icon('bi-clipboard-check')->iconColor('#4f81cf')->group('nav.operations')->order(12)
                ->permissions('operations.attendance.view'),
            AppMenuItem::make('nav.plans', '/cms/operations/lesson-planning')
                ->icon('bi-calendar-check')->iconColor('#6a78c8')->group('nav.operations')->order(13)
                ->permissions('operations.plans.view')->badge('nav.approval'),
            AppMenuItem::make('nav.tuition', '/cms/operations/tuition-fees')
                ->icon('bi-cash-stack')->iconColor('#32a06f')->group('nav.operations')->order(14)
                ->permissions('operations.tuition.view'),
            AppMenuItem::make('nav.files', '/cms/operations/file-library')
                ->icon('bi-folder2-open')->iconColor('#c97846')->group('nav.operations')->order(15)
                ->permissions('operations.files.view'),
            AppMenuItem::make('nav.coaches', '/cms/operations/coaches')
                ->icon('bi-person-badge')->iconColor('#4f8bc8')->group('nav.operations')->order(16)
                ->permissions('operations.coaches.view'),
            AppMenuItem::make('nav.studentProfiles', '/cms/operations/students')
                ->icon('bi-person-lines-fill')->iconColor('#5f77cf')->group('nav.operations')->order(17)
                ->permissions('operations.students.view'),

            // Member group
            AppMenuItem::make('nav.myMembership', '/cms/member/my-membership')
                ->icon('bi-card-checklist')->iconColor('#3f8f6f')->group('nav.member')->order(20)
                ->permissions('member.dashboard.view'),
            AppMenuItem::make('nav.myClasses', '/cms/member/my-classes')
                ->icon('bi-people')->iconColor('#3f7ec4')->group('nav.member')->order(21)
                ->permissions('member.dashboard.view'),
            AppMenuItem::make('nav.mySchedule', '/cms/member/my-schedule')
                ->icon('bi-calendar-check')->iconColor('#6a78c8')->group('nav.member')->order(22)
                ->permissions('member.dashboard.view'),

            // Coach group
            AppMenuItem::make('nav.myClasses', '/cms/coach/my-classes')
                ->icon('bi-people')->iconColor('#3f7ec4')->group('nav.coach')->order(30)
                ->permissions('coach.dashboard.view'),
            AppMenuItem::make('nav.myStudents', '/cms/coach/my-students')
                ->icon('bi-clipboard-check')->iconColor('#4f81cf')->group('nav.coach')->order(31)
                ->permissions('coach.dashboard.view'),
            AppMenuItem::make('nav.teachingSchedule', '/cms/coach/my-teaching-schedule')
                ->icon('bi-calendar-check')->iconColor('#6a78c8')->group('nav.coach')->order(32)
                ->permissions('coach.dashboard.view'),
        ]);
    }
}
