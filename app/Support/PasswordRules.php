<?php

namespace App\Support;

class PasswordRules
{
    /**
     * @return array<int, mixed>
     */
    public static function forUsers(): array
    {
        $rules = ['required', 'string', 'confirmed'];

        if (settings('security.require_strong_passwords', true)) {
            $rules[] = 'min:12';
            $rules[] = 'regex:/[a-z]/';
            $rules[] = 'regex:/[A-Z]/';
            $rules[] = 'regex:/[0-9]/';
        } else {
            $rules[] = 'min:8';
        }

        return $rules;
    }
}
