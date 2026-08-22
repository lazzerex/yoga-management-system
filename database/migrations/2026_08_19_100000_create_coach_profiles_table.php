<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->text('certifications')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('coach_class_type', function (Blueprint $table) {
            $table->foreignId('coach_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_type_id')->constrained()->cascadeOnDelete();

            $table->primary(['coach_profile_id', 'class_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_class_type');
        Schema::dropIfExists('coach_profiles');
    }
};
