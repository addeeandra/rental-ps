<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:255', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'sales_price' => ['required', 'numeric', 'min:0'],
            'rental_price' => ['required', 'numeric', 'min:0'],
            'uom' => ['required', 'string', 'max:255'],
            'rental_duration' => ['required', 'string', Rule::in(['hour', 'day', 'week', 'month'])],
            'components' => ['nullable', 'array', 'max:2'],
            'components.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'components.*.slot' => ['required', 'integer', 'in:1,2'],
            'components.*.qty_per_product' => ['required', 'numeric', 'min:0.001'],
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
            'code.unique' => 'The product code has already been taken.',
            'name.required' => 'Product name is required.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'The selected category does not exist.',
            'sales_price.required' => 'Sales price is required.',
            'sales_price.min' => 'Sales price must be at least 0.',
            'rental_price.required' => 'Rental price is required.',
            'rental_price.min' => 'Rental price must be at least 0.',
            'uom.required' => 'Unit of measure is required.',
            'rental_duration.required' => 'Rental duration is required.',
            'rental_duration.in' => 'Invalid rental duration selected.',
            'components.max' => 'A product can have at most 2 components.',
            'components.*.inventory_item_id.required' => 'Inventory item is required for each component.',
            'components.*.inventory_item_id.exists' => 'The selected inventory item does not exist.',
            'components.*.slot.required' => 'Component slot is required.',
            'components.*.slot.in' => 'Component slot must be 1 or 2.',
            'components.*.qty_per_product.required' => 'Quantity per product is required for each component.',
            'components.*.qty_per_product.min' => 'Quantity per product must be greater than 0.',
        ];
    }
}
