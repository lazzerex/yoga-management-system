<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\LoginLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureAdmin;
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

        Route::prefix('operations')->name('operations.')->group(function () {
            Route::middleware('role:admin,coach,member')->group(function () {
                Route::get('/yoga-center', fn () => inertia('Operations/YogaCenter'))->name('yoga-center');
                Route::get('/academy', fn () => inertia('Operations/Academy'))->name('academy');
            });

            Route::middleware('role:admin,coach')->group(function () {
                Route::get('/teacher-attendance', fn () => inertia('Operations/TeacherAttendance'))->name('teacher-attendance');
                Route::get('/lesson-planning', fn () => inertia('Operations/LessonPlanning'))->name('lesson-planning');
                Route::get('/file-library', fn () => inertia('Operations/FileLibrary'))->name('file-library');
            });

            Route::middleware('role:admin,member')->group(function () {
                Route::get('/tuition-fees', fn () => inertia('Operations/TuitionFees'))->name('tuition-fees');
            });
        });

        Route::middleware([EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
            Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::get('/login-logs', [LoginLogController::class, 'index'])->name('login-logs.index');
            Route::get('/login-logs/export', [LoginLogController::class, 'export'])->name('login-logs.export');
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
        });
    });
});
