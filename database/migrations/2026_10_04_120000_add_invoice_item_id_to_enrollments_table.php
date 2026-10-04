<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Restricted, not cascaded: DeleteInvoiceAction blocks the delete instead.
            $table->foreignId('invoice_item_id')->nullable()->after('class_session_id')
                ->constrained()->restrictOnDelete();

            $table->index(['invoice_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['invoice_item_id', 'status']);
            $table->dropConstrainedForeignId('invoice_item_id');
        });
    }
};
