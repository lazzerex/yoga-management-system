<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('issued_at');
            $table->date('due_date');
            $table->string('status', 20)->default('unpaid');
            $table->unsignedBigInteger('total_amount');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['student_profile_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
