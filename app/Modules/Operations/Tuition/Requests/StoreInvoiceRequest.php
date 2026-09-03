<?php

namespace App\Modules\Operations\Tuition\Requests;

use App\Models\Branch;
use App\Models\StudentProfile;
use App\Models\TuitionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return;
        }

        // The Select posts '' for a free-text line; integer validation rejects that.
        $this->merge(['items' => array_map(function ($item) {
            if (is_array($item) && ($item['tuition_plan_id'] ?? null) === '') {
                $item['tuition_plan_id'] = null;
            }

            return $item;
        }, $items)]);
    }

    public function rules(): array
    {
        return [
            'student_profile_id' => ['required', 'integer', Rule::exists(StudentProfile::class, 'id')],
            'branch_id' => ['required', 'integer', Rule::exists(Branch::class, 'id')],
            'issued_at' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issued_at'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.tuition_plan_id' => ['nullable', 'integer', Rule::exists(TuitionPlan::class, 'id')],
            'items.*.description' => ['required_without:items.*.tuition_plan_id', 'nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.unit_price' => ['required_without:items.*.tuition_plan_id', 'nullable', 'integer', 'min:0', 'max:9999999999'],
        ];
    }
}
