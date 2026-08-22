<?php

namespace App\Modules\Operations\ClassSchedule\Requests;

use App\Models\ClassSchedule;
use App\Models\CoachProfile;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;

class StoreClassScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'room_id' => [
                'required', 'integer', 'exists:rooms,id',
                function ($attribute, $value, $fail) {
                    $room = Room::find($value);
                    if ($room && (int) $room->branch_id !== (int) $this->branch_id) {
                        $fail(__('operations.roomMustBelongToBranch'));
                    }
                },
            ],
            'class_type_id' => ['required', 'integer', 'exists:class_types,id'],
            'coach_profile_id' => [
                'required', 'integer', 'exists:coach_profiles,id',
                function ($attribute, $value, $fail) {
                    if (! CoachProfile::where('id', $value)->where('is_active', true)->exists()) {
                        $fail(__('operations.coachMustBeActive'));
                    }
                },
            ],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'capacity' => [
                'required', 'integer', 'min:1',
                function ($attribute, $value, $fail) {
                    $room = Room::find($this->room_id);
                    if ($room && $value > $room->capacity) {
                        $fail(__('operations.capacityExceedsRoom'));
                    }
                },
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasConflict()) {
                $validator->errors()->add('room_id', __('operations.scheduleConflict'));
            }
        });
    }

    protected function hasConflict(): bool
    {
        return ClassSchedule::active()
            ->where('day_of_week', $this->day_of_week)
            ->where('start_time', $this->start_time)
            ->when($this->route('classSchedule'), fn ($q, $self) => $q->whereKeyNot($self))
            ->where(fn ($q) => $q->where('room_id', $this->room_id)->orWhere('coach_profile_id', $this->coach_profile_id))
            ->exists();
    }
}
