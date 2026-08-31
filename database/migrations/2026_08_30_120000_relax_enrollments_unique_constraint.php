<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the replacement index first: student_profile_id stays the leftmost
        // column, so its foreign key no longer depends on the unique index below.
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['student_profile_id', 'status']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['student_profile_id', 'class_session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_profile_id', 'class_session_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['student_profile_id', 'status']);
        });
    }
};
