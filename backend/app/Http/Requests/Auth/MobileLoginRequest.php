<?php

namespace App\Http\Requests\Auth;

class MobileLoginRequest extends LoginRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
