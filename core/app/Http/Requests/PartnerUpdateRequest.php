<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerUpdateRequest extends FormRequest
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
        $partnerId = $this->route('partner');

        return [
            'code' => ['nullable', 'string', 'max:255', Rule::unique('partners', 'code')->ignore($partnerId)],
            'type' => ['required', 'string', 'in:Client,Supplier,Supplier & Client'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'mobile_phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('partners', 'email')->ignore($partnerId)],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'gmap_url' => ['nullable', 'url', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'This partner code is already in use.',
            'type.required' => 'Please select a partner type.',
            'type.in' => 'The selected partner type is invalid.',
            'name.required' => 'Partner name is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already associated with another partner.',
            'gmap_url.url' => 'Please provide a valid Google Maps URL.',
            'website.url' => 'Please provide a valid website URL.',
        ];
    }
}
