<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuition_plans', function (Blueprint $table) {
            $table->id();
            // Null branch means the plan is sold at every branch.
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->unsignedBigInteger('price_amount');
            $table->unsignedSmallInteger('session_count')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_plans');
    }
};
