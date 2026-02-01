<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => Partner::factory()->supplier(),
            'name' => fake()->unique()->words(3, true),
            'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
            'unit' => fake()->randomElement(['pcs', 'kg', 'ltr', 'box', 'pack']),
            'cost' => fake()->randomFloat(2, 1000, 100000),
            'default_share_percent' => fake()->randomFloat(2, 5, 30),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the inventory item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific owner (Partner/Supplier).
     */
    public function forOwner(Partner $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $owner->id,
        ]);
    }

    /**
     * Set a custom share percentage.
     */
    public function withSharePercent(float $percent): static
    {
        return $this->state(fn (array $attributes) => [
            'default_share_percent' => $percent,
        ]);
    }
}
