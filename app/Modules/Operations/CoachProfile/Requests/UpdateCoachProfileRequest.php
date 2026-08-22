<?php

namespace App\Modules\Operations\CoachProfile\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:2000'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'certifications' => ['nullable', 'string', 'max:1000'],
            'class_type_ids' => ['array'],
            'class_type_ids.*' => ['integer', 'exists:class_types,id'],
            'is_active' => ['boolean'],
        ];
    }
}
