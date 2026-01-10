<?php

namespace Database\Factories;

use App\Enums\RentalDuration;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $products = [
            'PlayStation 5',
            'PlayStation 4 Pro',
            'PlayStation 3',
            'Nintendo Switch OLED',
            'Xbox Series X',
            'Gaming Monitor 27"',
            'Gaming Monitor 32"',
            'Extra Controller',
            'HDMI Cable',
            'VR Headset',
        ];

        $uoms = ['pcs', 'unit', 'set'];
        $rentalDurations = [RentalDuration::Hour, RentalDuration::Day, RentalDuration::Week, RentalDuration::Month];

        return [
            'name' => fake()->randomElement($products) . ' - ' . fake()->word(),
            'description' => fake()->optional()->sentence(),
            'category_id' => Category::factory(),
            'sales_price' => fake()->randomFloat(2, 0, 5000000),
            'rental_price' => fake()->randomFloat(2, 0, 500000),
            'uom' => fake()->randomElement($uoms),
            'rental_duration' => fake()->randomElement($rentalDurations),
        ];
    }
}
