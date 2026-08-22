<?php

use App\Modules\Admin\AuditLog\Controllers\AuditLogController;
use App\Modules\Admin\LoginLog\Controllers\LoginLogController;
use App\Modules\Admin\User\Controllers\UserController;
use App\Modules\Operations\Branch\Controllers\BranchController;
use App\Modules\Operations\ClassSchedule\Controllers\ClassScheduleController;
use App\Modules\Operations\ClassSession\Controllers\ClassSessionController;
use App\Modules\Operations\ClassType\Controllers\ClassTypeController;
use App\Modules\Operations\CoachProfile\Controllers\CoachProfileController;
use App\Modules\Operations\Room\Controllers\RoomController;
use App\Modules\Operations\StudentProfile\Controllers\StudentProfileController;
use App\Modules\Profile\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('cms')->group(function () {
    Route::get('/', function () {
        return redirect()->route('cms.dashboard');
    });

    Route::middleware(['auth'])->group(function () {
        Route::get('/dashboard', fn () => inertia('Dashboard'))->name('cms.dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('cms.profile.show');

        Route::prefix('operations')->name('operations.')->group(function () {
            Route::middleware('permission:operations.center.view')->group(function () {
                Route::get('/yoga-center', [BranchController::class, 'index'])->name('yoga-center');
            });

            Route::middleware('permission:operations.sessions.view')->group(function () {
                Route::get('/academy', [ClassSessionController::class, 'index'])->name('academy');
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
                Route::get('/teacher-attendance', fn () => inertia('Operations/TeacherAttendance'))->name('teacher-attendance');
            });

            Route::middleware('permission:operations.plans.view')->group(function () {
                Route::get('/lesson-planning', fn () => inertia('Operations/LessonPlanning'))->name('lesson-planning');

            });

            Route::middleware('permission:operations.files.view')->group(function () {
                Route::get('/file-library', fn () => inertia('Operations/FileLibrary'))->name('file-library');
            });

            Route::middleware('permission:operations.tuition.view')->group(function () {
                Route::get('/tuition-fees', fn () => inertia('Operations/TuitionFees'))->name('tuition-fees');
            });
        });

        Route::middleware('permission:member.dashboard.view')->prefix('member')->name('member.')->group(function () {
            Route::get('/my-membership', fn () => inertia('Member/MyMembership'))->name('my-membership');
            Route::get('/my-classes', fn () => inertia('Member/MyClasses'))->name('my-classes');
            Route::get('/my-schedule', fn () => inertia('Member/MySchedule'))->name('my-schedule');
        });

        Route::middleware('permission:coach.dashboard.view')->prefix('coach')->name('coach.')->group(function () {
            Route::get('/my-classes', fn () => inertia('Coach/MyClasses'))->name('my-classes');
            Route::get('/my-students', fn () => inertia('Coach/MyStudents'))->name('my-students');
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
            Route::middleware('permission:admin.form-demo.view')->group(function () {
                Route::get('/form-demo', fn () => inertia('Admin/FormDemo'))->name('form-demo');
            });
            Route::middleware('permission:admin.settings.view')->group(function () {
                Route::get('/settings/general', fn () => inertia('Admin/Settings/Mock', ['title' => 'General']))->name('settings.general');
            });
            Route::middleware('permission:admin.settings.system.view')->group(function () {
                Route::get('/settings/system/general', fn () => inertia('Admin/Settings/Mock', ['title' => 'System / General']))->name('settings.system.general');
                Route::get('/settings/system/advanced', fn () => inertia('Admin/Settings/Mock', ['title' => 'System / Advanced']))->name('settings.system.advanced');
            });
        });
    });
});
