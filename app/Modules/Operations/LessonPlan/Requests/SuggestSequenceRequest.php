<?php

namespace App\Modules\Operations\LessonPlan\Requests;

use App\Models\ClassType;
use App\Models\LessonPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuggestSequenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * These four fields are the whole input to the prompt. Nothing student-related is
     * accepted here, and nothing may be added: see the privacy rule in the Week 11 plan.
     */
    public function rules(): array
    {
        return [
            'class_type_id' => ['required', 'integer', Rule::exists(ClassType::class, 'id')],
            'level' => ['required', Rule::in(LessonPlan::LEVELS)],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'objective' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
