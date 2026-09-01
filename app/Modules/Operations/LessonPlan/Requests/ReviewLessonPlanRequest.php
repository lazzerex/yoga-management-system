<?php

namespace App\Modules\Operations\LessonPlan\Requests;

use App\Models\LessonPlanReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(LessonPlanReview::ACTIONS)],
            'comment' => ['required_if:action,rejected', 'nullable', 'string', 'max:1000'],
        ];
    }
}
