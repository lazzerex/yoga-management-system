<?php

namespace App\Modules\Operations\LessonPlan\Requests;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\LessonPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('class_session_id') === '') {
            $this->merge(['class_session_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists(Branch::class, 'id')],
            'class_type_id' => ['required', 'integer', Rule::exists(ClassType::class, 'id')],
            'class_session_id' => ['nullable', 'integer', Rule::exists(ClassSession::class, 'id')],
            'title' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'asana_sequence' => ['required', 'string', 'max:5000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'level' => ['required', Rule::in(LessonPlan::LEVELS)],
        ];
    }
}
