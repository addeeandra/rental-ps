<?php

namespace App\Http\Requests;

use App\Enums\StockMovementReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementStoreRequest extends FormRequest
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
            'inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'string', Rule::enum(StockMovementReason::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'inventory_item_id.required' => 'Inventory item is required.',
            'inventory_item_id.exists' => 'The selected inventory item does not exist.',
            'warehouse_id.required' => 'Warehouse is required.',
            'warehouse_id.exists' => 'The selected warehouse does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.not_in' => 'Quantity cannot be zero.',
            'reason.required' => 'Movement reason is required.',
            'reason.Illuminate\Validation\Rules\Enum' => 'Invalid movement reason selected.',
        ];
    }
}
