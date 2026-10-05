<?php

use App\Modules\Admin\AuditLog\Controllers\AuditLogController;
use App\Modules\Admin\LoginLog\Controllers\LoginLogController;
use App\Modules\Admin\Settings\Controllers\SettingsController;
use App\Modules\Admin\User\Controllers\UserController;
use App\Modules\Dashboard\Controllers\DashboardController;
use App\Modules\Operations\Attendance\Controllers\AttendanceController;
use App\Modules\Operations\Branch\Controllers\BranchController;
use App\Modules\Operations\ClassSchedule\Controllers\ClassScheduleController;
use App\Modules\Operations\ClassSession\Controllers\ClassSessionController;
use App\Modules\Operations\ClassType\Controllers\ClassTypeController;
use App\Modules\Operations\CoachProfile\Controllers\CoachProfileController;
use App\Modules\Operations\Enrollment\Controllers\EnrollmentController;
use App\Modules\Operations\LessonPlan\Controllers\LessonPlanController;
use App\Modules\Operations\Media\Controllers\FileLibraryController;
use App\Modules\Operations\Media\Controllers\MediaController;
use App\Modules\Operations\Room\Controllers\RoomController;
use App\Modules\Operations\StudentProfile\Controllers\StudentProfileController;
use App\Modules\Operations\Tuition\Controllers\InvoiceController;
use App\Modules\Operations\Tuition\Controllers\MembershipController;
use App\Modules\Operations\Tuition\Controllers\TuitionPlanController;
use App\Modules\Profile\Controllers\LocaleController;
use App\Modules\Profile\Controllers\NotificationController;
use App\Modules\Profile\Controllers\ProfileController;
use App\Modules\Search\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('cms')->group(function () {
    Route::get('/', function () {
        return redirect()->route('cms.dashboard');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('cms.dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('cms.profile.show');
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('cms.profile.avatar');
        Route::post('/profile/notification-preferences', [ProfileController::class, 'updateNotificationPreferences'])->name('cms.profile.notification-preferences');
        Route::post('/locale', [LocaleController::class, 'update'])->name('cms.locale.update');

        Route::get('/search', SearchController::class)->middleware('throttle:30,1')->name('cms.search');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('cms.notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('cms.notifications.read-all');
        Route::delete('/notifications', [NotificationController::class, 'clear'])->name('cms.notifications.clear');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('cms.notifications.read');

        Route::prefix('operations')->name('operations.')->group(function () {
            Route::middleware('permission:operations.center.view')->group(function () {
                Route::get('/yoga-center', [BranchController::class, 'index'])->name('yoga-center');
            });

            Route::middleware('permission:operations.sessions.view')->group(function () {
                Route::get('/academy', [ClassSessionController::class, 'index'])->name('academy');
                Route::get('/class-sessions/{classSession}', [ClassSessionController::class, 'show'])->name('class-sessions.show');
            });

            Route::middleware('permission:operations.sessions.manage')->group(function () {
                Route::prefix('class-schedules')->name('class-schedules.')->group(function () {
                    Route::get('/create', [ClassScheduleController::class, 'create'])->name('create');
                    Route::post('/', [ClassScheduleController::class, 'store'])->name('store');
                    Route::post('/generate-sessions', [ClassScheduleController::class, 'generateSessions'])->name('generate-sessions');
                    Route::get('/{classSchedule}/edit', [ClassScheduleController::class, 'edit'])->name('edit');
                    Route::patch('/{classSchedule}', [ClassScheduleController::class, 'update'])->name('update');
                    Route::delete('/{classSchedule}', [ClassScheduleController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('class-sessions')->name('class-sessions.')->group(function () {
                    Route::get('/{classSession}/edit', [ClassSessionController::class, 'edit'])->name('edit');
                    Route::patch('/{classSession}', [ClassSessionController::class, 'update'])->name('update');
                    Route::post('/{classSession}/cancel', [ClassSessionController::class, 'cancel'])->name('cancel');
                });
            });

            Route::middleware('permission:operations.center.manage')->group(function () {
                Route::prefix('branches')->name('branches.')->group(function () {
                    Route::get('/create', [BranchController::class, 'create'])->name('create');
                    Route::post('/', [BranchController::class, 'store'])->name('store');
                    Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit');
                    Route::patch('/{branch}', [BranchController::class, 'update'])->name('update');
                    Route::delete('/{branch}', [BranchController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('rooms')->name('rooms.')->group(function () {
                    Route::get('/create', [RoomController::class, 'create'])->name('create');
                    Route::post('/', [RoomController::class, 'store'])->name('store');
                    Route::get('/{room}/edit', [RoomController::class, 'edit'])->name('edit');
                    Route::patch('/{room}', [RoomController::class, 'update'])->name('update');
                    Route::delete('/{room}', [RoomController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('class-types')->name('class-types.')->group(function () {
                    Route::get('/create', [ClassTypeController::class, 'create'])->name('create');
                    Route::post('/', [ClassTypeController::class, 'store'])->name('store');
                    Route::get('/{classType}/edit', [ClassTypeController::class, 'edit'])->name('edit');
                    Route::patch('/{classType}', [ClassTypeController::class, 'update'])->name('update');
                    Route::delete('/{classType}', [ClassTypeController::class, 'destroy'])->name('destroy');
                });
            });

            Route::middleware('permission:operations.coaches.view')->group(function () {
                Route::get('/coaches', [CoachProfileController::class, 'index'])->name('coaches.index');
            });

            Route::middleware('permission:operations.coaches.manage')->prefix('coaches')->name('coaches.')->group(function () {
                Route::get('/create', [CoachProfileController::class, 'create'])->name('create');
                Route::post('/', [CoachProfileController::class, 'store'])->name('store');
                Route::get('/{coachProfile}/edit', [CoachProfileController::class, 'edit'])->name('edit');
                Route::patch('/{coachProfile}', [CoachProfileController::class, 'update'])->name('update');
                Route::delete('/{coachProfile}', [CoachProfileController::class, 'destroy'])->name('destroy');
            });

            Route::middleware('permission:operations.students.view')->group(function () {
                Route::get('/students', [StudentProfileController::class, 'index'])->name('students.index');
            });

            Route::middleware('permission:operations.students.manage')->prefix('students')->name('students.')->group(function () {
                Route::get('/create', [StudentProfileController::class, 'create'])->name('create');
                Route::post('/', [StudentProfileController::class, 'store'])->name('store');
                Route::get('/{studentProfile}/edit', [StudentProfileController::class, 'edit'])->name('edit');
                Route::patch('/{studentProfile}', [StudentProfileController::class, 'update'])->name('update');
                Route::delete('/{studentProfile}', [StudentProfileController::class, 'destroy'])->name('destroy');
            });

            Route::middleware('permission:operations.attendance.view')->group(function () {
                Route::get('/teacher-attendance', [AttendanceController::class, 'index'])->name('teacher-attendance');
                Route::get('/attendance/reports', [AttendanceController::class, 'reports'])->name('attendance.reports');
                Route::get('/attendance/reports/pdf', [AttendanceController::class, 'reportPdf'])->name('attendance.reports.pdf');
                Route::get('/attendance/{classSession}', [AttendanceController::class, 'roster'])->name('attendance.roster');
            });

            Route::middleware('permission:operations.attendance.manage')->prefix('attendance')->name('attendance.')->group(function () {
                Route::post('/{classSession}/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
                Route::post('/{classSession}/check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
                Route::post('/{classSession}/mark', [AttendanceController::class, 'mark'])->name('mark');
            });

            Route::middleware('permission:operations.plans.view')->group(function () {
                Route::get('/lesson-planning', [LessonPlanController::class, 'index'])->name('lesson-planning');
            });

            // Static segments are declared before /{lessonPlan} or the wildcard swallows them.
            Route::prefix('lesson-planning')->name('lesson-plans.')->group(function () {
                Route::middleware('permission:operations.plans.manage')->group(function () {
                    Route::get('/create', [LessonPlanController::class, 'create'])->name('create');
                    Route::post('/', [LessonPlanController::class, 'store'])->name('store');
                });

                Route::middleware(['permission:operations.plans.ai.suggest', 'throttle:10,60'])->group(function () {
                    Route::post('/suggest', [LessonPlanController::class, 'suggest'])->name('suggest');
                });

                Route::middleware('permission:operations.plans.review')->group(function () {
                    Route::get('/pending', [LessonPlanController::class, 'pending'])->name('pending');
                    Route::post('/{lessonPlan}/review', [LessonPlanController::class, 'review'])->name('review');
                    Route::post('/{lessonPlan}/check', [LessonPlanController::class, 'check'])->middleware('throttle:10,60')->name('check');
                });

                Route::middleware('permission:operations.plans.view')->group(function () {
                    Route::get('/{lessonPlan}', [LessonPlanController::class, 'show'])->name('show');
                    Route::get('/{lessonPlan}/pdf', [LessonPlanController::class, 'pdf'])->name('pdf');
                });

                Route::middleware('permission:operations.plans.manage')->group(function () {
                    Route::get('/{lessonPlan}/edit', [LessonPlanController::class, 'edit'])->name('edit');
                    Route::patch('/{lessonPlan}', [LessonPlanController::class, 'update'])->name('update');
                    Route::delete('/{lessonPlan}', [LessonPlanController::class, 'destroy'])->name('destroy');
                    Route::post('/{lessonPlan}/submit', [LessonPlanController::class, 'submit'])->name('submit');
                });
            });

            Route::middleware('permission:operations.files.view')->group(function () {
                Route::get('/file-library', [FileLibraryController::class, 'index'])->name('file-library');
            });

            // No permission middleware on the download: a file is authorised by the record
            // it hangs off, so a member can fetch their own avatar without library access.
            Route::get('/files/{media}', [MediaController::class, 'show'])->whereNumber('media')->name('files.show');
            Route::delete('/files/{media}', [MediaController::class, 'destroy'])->whereNumber('media')->name('files.destroy');

            Route::middleware('permission:operations.tuition.view')->group(function () {
                Route::get('/tuition-fees', [InvoiceController::class, 'index'])->name('tuition-fees');
            });

            // Static segments are declared before /{invoice} or the wildcard swallows them.
            Route::prefix('tuition-fees')->group(function () {
                Route::middleware('permission:operations.tuition.manage')->name('tuition-plans.')->prefix('plans')->group(function () {
                    Route::get('/', [TuitionPlanController::class, 'index'])->name('index');
                    Route::get('/create', [TuitionPlanController::class, 'create'])->name('create');
                    Route::post('/', [TuitionPlanController::class, 'store'])->name('store');
                    Route::get('/{tuitionPlan}/edit', [TuitionPlanController::class, 'edit'])->name('edit');
                    Route::patch('/{tuitionPlan}', [TuitionPlanController::class, 'update'])->name('update');
                    Route::delete('/{tuitionPlan}', [TuitionPlanController::class, 'destroy'])->name('destroy');
                });

                Route::name('invoices.')->group(function () {
                    Route::middleware('permission:operations.tuition.view')->group(function () {
                        Route::get('/export', [InvoiceController::class, 'export'])->name('export');
                    });

                    Route::middleware('permission:operations.tuition.manage')->group(function () {
                        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
                        Route::post('/', [InvoiceController::class, 'store'])->name('store');
                    });

                    Route::middleware('permission:operations.tuition.view')->group(function () {
                        Route::get('/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('show');
                    });

                    // No permission middleware: a member holds no tuition permission and
                    // still needs the receipt for their own invoice, so ownership decides.
                    Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])->whereNumber('invoice')->name('pdf');

                    Route::middleware('permission:operations.tuition.manage')->whereNumber('invoice')->group(function () {
                        Route::post('/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('payments.store');
                        Route::post('/{invoice}/payments/{payment}/void', [InvoiceController::class, 'voidPayment'])->scopeBindings()->name('payments.void');
                        Route::post('/{invoice}/waive', [InvoiceController::class, 'waive'])->name('waive');
                        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
                    });
                });
            });

            Route::middleware('permission:operations.enrollments.view')->group(function () {
                Route::get('/enrollments', [EnrollmentController::class, 'adminIndex'])->name('enrollments.index');
            });

            Route::middleware('permission:operations.enrollments.manage')->group(function () {
                Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'adminCancel'])->name('enrollments.admin-cancel');
            });
        });

        Route::middleware('permission:member.dashboard.view')->prefix('member')->name('member.')->group(function () {
            Route::get('/my-membership', [MembershipController::class, 'show'])->name('my-membership');
            Route::get('/my-classes', [EnrollmentController::class, 'index'])->name('my-classes');
            Route::get('/book', [EnrollmentController::class, 'browse'])->name('classes.book');
            Route::get('/my-schedule', [EnrollmentController::class, 'mySchedule'])->name('my-schedule');

            Route::middleware('permission:member.enrollments.manage')->group(function () {
                Route::post('/class-sessions/{classSession}/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
                Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
            });
        });

        Route::middleware('permission:coach.dashboard.view')->prefix('coach')->name('coach.')->group(function () {
            Route::get('/my-classes', [ClassSessionController::class, 'myClasses'])->name('my-classes');
            Route::get('/my-teaching-schedule', [ClassSessionController::class, 'myTeachingSchedule'])->name('my-teaching-schedule');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::middleware('permission:admin.users.view')->group(function () {
                Route::get('/users', [UserController::class, 'index'])->name('users.index');
                Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
                Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            });
            Route::middleware('permission:admin.users.manage')->group(function () {
                Route::post('/users', [UserController::class, 'store'])->name('users.store');
                Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
                Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
                Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            });
            Route::middleware('permission:admin.login-logs.view')->group(function () {
                Route::get('/login-logs', [LoginLogController::class, 'index'])->name('login-logs.index');
                Route::get('/login-logs/export', [LoginLogController::class, 'export'])->name('login-logs.export');
            });
            Route::middleware('permission:admin.audit-logs.view')->group(function () {
                Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
                Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
            });
            Route::middleware('permission:admin.settings.view')->group(function () {
                Route::get('/settings/general', [SettingsController::class, 'general'])->name('settings.general');
            });
            Route::middleware('permission:admin.settings.manage')->group(function () {
                Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            });
            Route::middleware('permission:admin.settings.system.view')->group(function () {
                Route::get('/settings/system/general', [SettingsController::class, 'systemGeneral'])->name('settings.system.general');
                Route::get('/settings/system/advanced', [SettingsController::class, 'systemAdvanced'])->name('settings.system.advanced');
            });
        });
    });
});
