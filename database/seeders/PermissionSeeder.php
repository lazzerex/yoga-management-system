<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'operations.center.view',
            'operations.center.manage',
            'operations.coaches.view',
            'operations.coaches.manage',
            'operations.students.view',
            'operations.students.view.any',
            'operations.students.manage',
            'operations.students.medical.view',
            'operations.sessions.view',
            'operations.sessions.manage',
            'operations.attendance.view',
            'operations.attendance.manage',
            'operations.attendance.manage.any',
            'operations.plans.view',
            'operations.plans.view.any',
            'operations.plans.manage',
            'operations.plans.review',
            'operations.files.view',
            'operations.files.manage',
            'operations.tuition.view',
            'operations.tuition.manage',
            'operations.enrollments.view',
            'operations.enrollments.manage',
            'member.dashboard.view',
            'member.enrollments.manage',
            'coach.dashboard.view',
            'admin.users.view',
            'admin.users.manage',
            'admin.login-logs.view',
            'admin.audit-logs.view',
            'admin.form-demo.view',
            'admin.settings.view',
            'admin.settings.system.view',
            'admin.dashboard.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Mirrors the current role:x,y route groups in routes/web.php exactly —
        // see menu.md Step 10.7 for the route side of this same mapping.
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'operations.center.view',
            'operations.center.manage',
            'operations.coaches.view',
            'operations.coaches.manage',
            'operations.students.view',
            'operations.students.view.any',
            'operations.students.manage',
            'operations.students.medical.view',
            'operations.sessions.view',
            'operations.sessions.manage',
            'operations.attendance.view',
            'operations.attendance.manage',
            'operations.attendance.manage.any',
            'operations.plans.view',
            'operations.plans.view.any',
            'operations.plans.review',
            'operations.files.view',
            'operations.files.manage',
            'operations.tuition.view',
            'operations.tuition.manage',
            'operations.enrollments.view',
            'operations.enrollments.manage',
            'admin.users.view',
            'admin.users.manage',
            'admin.login-logs.view',
            'admin.audit-logs.view',
            'admin.form-demo.view',
            'admin.settings.view',
            'admin.settings.system.view',
            'admin.dashboard.manage',
        ]);

        $coach = Role::firstOrCreate(['name' => 'coach']);
        $coach->syncPermissions([
            'operations.center.view',
            'operations.students.view',
            'operations.sessions.view',
            'operations.attendance.view',
            'operations.attendance.manage',
            'operations.plans.view',
            'operations.plans.manage',
            'operations.files.view',
            'coach.dashboard.view',
        ]);

        $member = Role::firstOrCreate(['name' => 'member']);
        $member->syncPermissions([
            'operations.center.view',
            'operations.sessions.view',
            'member.dashboard.view',
            'member.enrollments.manage',
        ]);
    }
}
