<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plan_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->string('comment', 1000)->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['lesson_plan_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_reviews');
    }
};
