<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'tuition_plan_id', 'description', 'quantity', 'unit_price', 'line_total', 'valid_from', 'valid_until', 'sessions_granted'])]
class InvoiceItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'line_total' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tuitionPlan(): BelongsTo
    {
        return $this->belongsTo(TuitionPlan::class);
    }
}
