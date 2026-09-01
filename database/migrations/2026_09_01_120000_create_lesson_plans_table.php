<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_session_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('objective')->nullable();
            $table->text('asana_sequence');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('level', 20);
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['coach_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
