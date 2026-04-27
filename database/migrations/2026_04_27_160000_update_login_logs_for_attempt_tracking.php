<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('login_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('status', 20)->default('success')->after('user_id');
            $table->string('attempted_identifier')->nullable()->after('status');
            $table->string('failure_reason', 40)->nullable()->after('attempted_identifier');
            $table->index(['status', 'logged_in_at']);
        });

        Schema::table('login_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['status', 'logged_in_at']);
            $table->dropColumn(['status', 'attempted_identifier', 'failure_reason']);
        });

        DB::table('login_logs')->whereNull('user_id')->delete();

        Schema::table('login_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('login_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
