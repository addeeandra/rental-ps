<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partner>
 */
class PartnerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Client', 'Supplier', 'Supplier & Client'];
        $provinces = ['Jawa Timur', 'Jawa Barat', 'Jawa Tengah', 'DKI Jakarta', 'Bali'];
        
        return [
            'type' => fake()->randomElement($types),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'mobile_phone' => fake()->optional()->phoneNumber(),
            'address_line_1' => fake()->optional()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'province' => fake()->randomElement($provinces),
            'postal_code' => fake()->optional()->postcode(),
            'country' => 'Indonesia',
            'gmap_url' => fake()->optional()->url(),
            'website' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
