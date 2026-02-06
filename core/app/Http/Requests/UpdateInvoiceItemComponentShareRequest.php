<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceItemComponentShareRequest extends FormRequest
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
            'share_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'share_percent.required' => 'Share percentage is required.',
            'share_percent.numeric' => 'Share percentage must be a number.',
            'share_percent.min' => 'Share percentage cannot be negative.',
            'share_percent.max' => 'Share percentage cannot exceed 100%.',
        ];
    }
}
