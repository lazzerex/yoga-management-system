<?php

namespace App\Modules\Operations\ClassSession\Requests;

use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ClassSession $session */
        $session = $this->route('classSession');

        return [
            'room_id' => [
                'required', 'integer', 'exists:rooms,id',
                function ($attribute, $value, $fail) use ($session) {
                    $room = Room::find($value);
                    if ($room && (int) $room->branch_id !== (int) $session->branch_id) {
                        $fail(__('operations.roomMustBelongToBranch'));
                    }
                },
            ],
            'coach_profile_id' => [
                'required', 'integer', 'exists:coach_profiles,id',
                function ($attribute, $value, $fail) {
                    if (! CoachProfile::where('id', $value)->where('is_active', true)->exists()) {
                        $fail(__('operations.coachMustBeActive'));
                    }
                },
            ],
            'capacity' => [
                'required', 'integer', 'min:1',
                function ($attribute, $value, $fail) {
                    $room = Room::find($this->room_id);
                    if ($room && $value > $room->capacity) {
                        $fail(__('operations.capacityExceedsRoom'));
                    }
                },
            ],
            'status' => ['required', Rule::in(['scheduled', 'cancelled', 'done'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->status === 'scheduled' && $this->hasConflict()) {
                $validator->errors()->add('room_id', __('operations.scheduleConflict'));
            }
        });
    }

    protected function hasConflict(): bool
    {
        /** @var ClassSession $session */
        $session = $this->route('classSession');

        return ClassSession::where('status', 'scheduled')
            ->where('session_date', $session->session_date)
            ->where('start_time', $session->start_time)
            ->whereKeyNot($session)
            ->where(fn ($q) => $q->where('room_id', $this->room_id)->orWhere('coach_profile_id', $this->coach_profile_id))
            ->exists();
    }
}
