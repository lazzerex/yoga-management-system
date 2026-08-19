<?php

namespace App\Modules\Operations\Room\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('rooms', 'name')->where('branch_id', $this->branch_id),
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
