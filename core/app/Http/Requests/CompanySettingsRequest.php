<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanySettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'invoice_number_prefix' => ['required', 'string', 'max:10', 'alpha_dash'],
            'invoice_default_terms' => ['nullable', 'string'],
            'invoice_default_notes' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048', 'mimes:png,jpg,jpeg'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required.',
            'invoice_number_prefix.required' => 'Invoice number prefix is required.',
            'invoice_number_prefix.alpha_dash' => 'Invoice prefix can only contain letters, numbers, dashes, and underscores.',
            'logo.image' => 'Logo must be an image file.',
            'logo.max' => 'Logo file size must not exceed 2MB.',
            'logo.mimes' => 'Logo must be a PNG, JPG, or JPEG file.',
        ];
    }
}
