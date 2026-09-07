<?php

namespace App\Modules\Admin\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'centre_name' => ['required', 'string', 'max:120'],
            'cancel_cutoff_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'default_locale' => ['required', 'string', 'in:en,vi'],
        ];
    }
}
