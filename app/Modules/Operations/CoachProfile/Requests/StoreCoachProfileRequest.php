<?php

namespace App\Modules\Operations\CoachProfile\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoachProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required', 'integer', 'exists:users,id',
                Rule::unique('coach_profiles', 'user_id'),
                function ($attribute, $value, $fail) {
                    if (User::find($value)?->role !== 'coach') {
                        $fail(__('operations.userMustBeCoach'));
                    }
                },
            ],
            'bio' => ['nullable', 'string', 'max:2000'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
            'certifications' => ['nullable', 'string', 'max:1000'],
            'class_type_ids' => ['array'],
            'class_type_ids.*' => ['integer', 'exists:class_types,id'],
            'is_active' => ['boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_avatar' => ['boolean'],
        ];
    }
}
