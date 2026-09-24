<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            && ($this->user()?->can('update', $user) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        $password = PasswordRules::forUsers();
        $password[0] = 'nullable';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => $password,
            'phone' => ['nullable', 'string', 'max:30'],
            'employee_code' => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_code')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department_id' => ['nullable', 'ulid', 'exists:departments,id'],
            'manager_id' => ['nullable', 'ulid', 'exists:users,id', Rule::notIn([$user->id])],
            'date_of_joining' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'theme' => ['nullable', Rule::in(['light', 'dark', 'system'])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['ulid', 'exists:roles,id'],
        ];
    }
}
