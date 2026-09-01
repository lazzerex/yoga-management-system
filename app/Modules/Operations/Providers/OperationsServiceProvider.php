<?php

namespace App\Modules\Operations\Providers;

use App\Support\Menu\AppMenuItem;
use App\Support\Menu\MenuRegistry;
use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Eventy;

class OperationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Eventy::addAction('register_backend_menu', function (MenuRegistry $menu) {
            $menu->addItems([
                AppMenuItem::make('nav.centers', '/cms/operations/yoga-center')
                    ->icon('bi-building')->iconColor('#d99a34')->group('nav.operations')->order(10)
                    ->permissions('operations.center.view'),
                AppMenuItem::make('nav.classes', '/cms/operations/academy')
                    ->icon('bi-people')->iconColor('#3fa07e')->group('nav.operations')->order(11)
                    ->permissions('operations.sessions.view'),
                AppMenuItem::make('nav.attendance', '/cms/operations/teacher-attendance')
                    ->icon('bi-clipboard-check')->iconColor('#4f81cf')->group('nav.operations')->order(12)
                    ->permissions('operations.attendance.view')
                    ->addItems([
                        AppMenuItem::make('nav.attendanceBoard', '/cms/operations/teacher-attendance')
                            ->icon('bi-clipboard-check')->permissions('operations.attendance.view'),
                        AppMenuItem::make('nav.attendanceReports', '/cms/operations/attendance/reports')
                            ->icon('bi-bar-chart')->permissions('operations.attendance.view'),
                    ]),
                AppMenuItem::make('nav.plans', '/cms/operations/lesson-planning')
                    ->icon('bi-calendar-check')->iconColor('#6a78c8')->group('nav.operations')->order(13)
                    ->permissions('operations.plans.view')->badge('nav.approval')
                    ->addItems([
                        AppMenuItem::make('nav.planList', '/cms/operations/lesson-planning')
                            ->icon('bi-calendar-check')->permissions('operations.plans.view'),
                        AppMenuItem::make('nav.planApprovalQueue', '/cms/operations/lesson-planning/pending')
                            ->icon('bi-clipboard-check')->permissions('operations.plans.review'),
                    ]),
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
                AppMenuItem::make('nav.enrollments', '/cms/operations/enrollments')
                    ->icon('bi-journal-check')->iconColor('#3fa07e')->group('nav.operations')->order(18)
                    ->permissions('operations.enrollments.view'),

                AppMenuItem::make('nav.myMembership', '/cms/member/my-membership')
                    ->icon('bi-card-checklist')->iconColor('#3f8f6f')->group('nav.member')->order(20)
                    ->permissions('member.dashboard.view'),
                AppMenuItem::make('nav.myClasses', '/cms/member/my-classes')
                    ->icon('bi-people')->iconColor('#3f7ec4')->group('nav.member')->order(21)
                    ->permissions('member.dashboard.view'),

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
        }, 10, 1);
    }
}
