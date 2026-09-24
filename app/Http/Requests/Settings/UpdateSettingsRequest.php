<?php

namespace App\Http\Requests\Settings;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $section = $this->route('section', 'company');

        return match ($section) {
            'company' => [
                'company_name' => ['required', 'string', 'max:255'],
                'company_email' => ['required', 'email', 'max:255'],
                'company_phone' => ['nullable', 'string', 'max:50'],
                'company_website' => ['nullable', 'url', 'max:255'],
                'company_address' => ['nullable', 'string', 'max:1000'],
                'company_gst_number' => ['nullable', 'string', 'max:50'],
                'company_pan_number' => ['nullable', 'string', 'max:20'],
            ],
            'branding' => [
                'logo' => ['nullable', 'image', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg'],
                'favicon' => ['nullable', 'file', 'max:512', 'mimes:png,ico,jpg,jpeg,webp,svg'],
                'remove_logo' => ['sometimes', 'boolean'],
                'remove_favicon' => ['sometimes', 'boolean'],
            ],
            'localization' => [
                'company_currency' => ['required', 'string', 'size:3'],
                'company_timezone' => ['required', 'timezone'],
                'company_date_format' => ['required', Rule::in(['d M Y', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y'])],
            ],
            'security' => [
                'security_session_timeout' => ['required', 'integer', 'min:15', 'max:1440'],
                'security_login_max_attempts' => ['required', 'integer', 'min:3', 'max:20'],
                'security_require_strong_passwords' => ['sometimes', 'boolean'],
            ],
            'email' => [
                'email_from_name' => ['required', 'string', 'max:255'],
                'email_from_address' => ['required', 'email', 'max:255'],
            ],
            'notifications' => [
                'notifications_email_enabled' => ['sometimes', 'boolean'],
                'notifications_in_app_enabled' => ['sometimes', 'boolean'],
            ],
            'system' => [
                'system_maintenance_message' => ['nullable', 'string', 'max:500'],
            ],
            default => [],
        };
    }
}
