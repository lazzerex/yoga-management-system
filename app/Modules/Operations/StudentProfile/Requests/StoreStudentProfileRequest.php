<?php

namespace App\Modules\Operations\StudentProfile\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentProfileRequest extends FormRequest
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
                Rule::unique('student_profiles', 'user_id'),
                function ($attribute, $value, $fail) {
                    if (User::find($value)?->role !== 'member') {
                        $fail(__('operations.userMustBeMember'));
                    }
                },
            ],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'goals' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_avatar' => ['boolean'],
        ];
    }
}
