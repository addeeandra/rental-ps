<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemUpdateRequest extends FormRequest
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
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_items', 'sku')->ignore($this->route('inventory_item')),
            ],
            'name' => ['required', 'string', 'max:191'],
            'owner_id' => ['required', 'exists:partners,id'],
            'unit' => ['nullable', 'string', 'max:50'],
            'cost' => ['required', 'numeric', 'min:0'],
            'default_share_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
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
            'sku.unique' => 'The SKU has already been taken.',
            'name.required' => 'Inventory item name is required.',
            'owner_id.required' => 'Owner (supplier) is required.',
            'owner_id.exists' => 'The selected owner does not exist.',
            'cost.required' => 'Cost is required.',
            'cost.min' => 'Cost must be at least 0.',
            'default_share_percent.required' => 'Default share percentage is required.',
            'default_share_percent.min' => 'Share percentage must be at least 0.',
            'default_share_percent.max' => 'Share percentage must not exceed 100.',
        ];
    }
}
