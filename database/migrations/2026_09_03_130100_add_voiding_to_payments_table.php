<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // A payment is never deleted: voiding keeps the row, its audit trail and
            // its proof file while taking the amount out of every balance.
            $table->string('status', 20)->default('recorded')->after('amount');
            $table->timestamp('voided_at')->nullable()->after('note');
            $table->foreignId('voided_by_user_id')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable()->after('voided_by_user_id');

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['invoice_id', 'status']);
            $table->dropConstrainedForeignId('voided_by_user_id');
            $table->dropColumn(['status', 'voided_at', 'void_reason']);
        });
    }
};
