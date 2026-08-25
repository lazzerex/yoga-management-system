<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_session_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('booked');
            $table->timestamp('enrolled_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['student_profile_id', 'class_session_id']);
            $table->index(['class_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
