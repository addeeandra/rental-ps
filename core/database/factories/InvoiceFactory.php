<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\OrderType;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiceDate = fake()->dateTimeBetween('-6 months', 'now');
        $dueDate = (clone $invoiceDate)->modify('+30 days');
        $orderType = fake()->randomElement(['sales', 'rental']);
        $subtotal = fake()->randomFloat(2, 100000, 10000000);
        $discount = fake()->randomFloat(2, 0, $subtotal * 0.1);
        $tax = fake()->randomFloat(2, 0, $subtotal * 0.11);
        $shipping = fake()->randomFloat(2, 0, 100000);
        $total = $subtotal - $discount + $tax + $shipping;
        
        $rentalData = [];
        if ($orderType === 'rental') {
            $rentalStart = fake()->dateTimeBetween('-3 months', '+1 month');
            $rentalEnd = (clone $rentalStart)->modify('+' . fake()->numberBetween(1, 30) . ' days');
            $rentalData = [
                'rental_start_date' => $rentalStart->format('Y-m-d'),
                'rental_end_date' => $rentalEnd->format('Y-m-d'),
                'delivery_time' => fake()->randomElement(['08:00', '09:00', '10:00', '11:00', '14:00']),
                'return_time' => fake()->randomElement(['16:00', '17:00', '18:00', '19:00', '20:00']),
            ];
        }

        return [
            'partner_id' => Partner::inRandomOrder()->first()?->id ?? Partner::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'reference_number' => fake()->optional()->bothify('REF-####-????'),
            'invoice_date' => $invoiceDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'order_type' => $orderType,
            'notes' => fake()->optional()->paragraph(),
            'terms' => fake()->optional()->sentence(10),
            'status' => fake()->randomElement([InvoiceStatus::Unpaid, InvoiceStatus::Partial, InvoiceStatus::Paid]),
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'shipping_fee' => $shipping,
            'total_amount' => $total,
            'paid_amount' => fake()->randomFloat(2, 0, $total),
            ...$rentalData,
        ];
    }
}
