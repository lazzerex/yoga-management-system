<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->id();

            // NOT NULL throughout: MySQL treats NULLs as distinct in a unique index.
            $table->string('event_key', 60);
            $table->foreignId('notifiable_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id');
            $table->date('sent_on');

            $table->unique(
                ['event_key', 'notifiable_id', 'subject_type', 'subject_id', 'sent_on'],
                'notification_dispatches_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_dispatches');
    }
};
