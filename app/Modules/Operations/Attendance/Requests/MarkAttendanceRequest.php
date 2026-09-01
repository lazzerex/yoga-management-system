<?php

namespace App\Modules\Operations\Attendance\Requests;

use App\Models\StudentAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'entries.*.status' => ['required', Rule::in(StudentAttendance::STATUSES)],
            'entries.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
