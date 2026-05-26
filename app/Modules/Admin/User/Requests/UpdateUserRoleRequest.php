<?php

namespace App\Modules\Admin\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest 
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'role' => ['required', 'in:admin,coach,member'],
        ];
    }
}