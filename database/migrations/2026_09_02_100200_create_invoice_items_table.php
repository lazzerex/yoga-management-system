<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tuition_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            // Entitlement copied off the plan when the line is billed, so later
            // price or duration edits never rewrite what a student already bought.
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedSmallInteger('sessions_granted')->nullable();
            $table->timestamps();

            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
