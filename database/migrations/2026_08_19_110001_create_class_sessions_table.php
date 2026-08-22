<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('coach_profile_id')->constrained()->restrictOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity');
            $table->string('status', 20)->default('scheduled');
            $table->boolean('is_overridden')->default(false);
            $table->timestamps();

            $table->unique(['class_schedule_id', 'session_date']);
            $table->index(['room_id', 'session_date', 'start_time']);
            $table->index(['coach_profile_id', 'session_date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
