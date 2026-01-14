<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('invoice')->is_editable;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'exists:partners,id'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'order_type' => ['required', Rule::in(['sales', 'rental'])],
            'rental_start_date' => ['required_if:order_type,rental', 'nullable', 'date'],
            'rental_duration' => ['required_if:order_type,rental', 'nullable', 'integer', 'min:1'],
            'rental_end_date' => ['nullable', 'date'], // Will be auto-calculated
            'delivery_time' => ['required_if:order_type,rental', 'nullable', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'], // Will be auto-calculated
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.product_id' => ['nullable', 'exists:products,id,deleted_at,NULL'],
            'line_items.*.description' => ['required', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->order_type === 'rental' &&
                $this->rental_start_date &&
                $this->rental_end_date &&
                $this->delivery_time &&
                $this->return_time) {
                
                try {
                    $deliveryDateTime = Carbon::parse($this->rental_start_date . ' ' . $this->delivery_time);
                    $returnDateTime = Carbon::parse($this->rental_end_date . ' ' . $this->return_time);

                    if ($returnDateTime->lte($deliveryDateTime)) {
                        $validator->errors()->add(
                            'return_time',
                            'Return date and time must be after delivery date and time.'
                        );
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('rental_dates', 'Invalid date or time format.');
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'partner_id.required' => 'Please select a customer.',
            'partner_id.exists' => 'The selected customer is invalid.',
            'invoice_date.required' => 'Invoice date is required.',
            'due_date.required' => 'Due date is required.',
            'order_type.required' => 'Please select an order type.',
            'order_type.in' => 'The selected order type is invalid.',
            'rental_start_date.required_if' => 'Rental start date is required for rental orders.',
            'rental_duration.required_if' => 'Rental duration is required for rental orders.',
            'rental_duration.integer' => 'Rental duration must be a whole number.',
            'rental_duration.min' => 'Rental duration must be at least 1 day.',
            'delivery_time.required_if' => 'Delivery time is required for rental orders.',
            'line_items.required' => 'At least one line item is required.',
            'line_items.min' => 'At least one line item is required.',
            'line_items.*.product_id.exists' => 'The selected product is invalid.',
            'line_items.*.description.required' => 'Item description is required.',
            'line_items.*.quantity.required' => 'Item quantity is required.',
            'line_items.*.quantity.min' => 'Item quantity must be at least 0.01.',
            'line_items.*.unit_price.required' => 'Item unit price is required.',
            'line_items.*.unit_price.min' => 'Item unit price cannot be negative.',
        ];
    }
}
