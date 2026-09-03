<?php

namespace App\Modules\Operations\Tuition\Requests;

use App\Models\Branch;
use App\Models\TuitionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTuitionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['branch_id', 'session_count', 'duration_days'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', Rule::exists(Branch::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(TuitionPlan::TYPES)],
            'price_amount' => ['required', 'integer', 'min:0', 'max:9999999999'],
            'session_count' => ['nullable', 'integer', 'min:1', 'max:9999', 'required_if:type,pack'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
