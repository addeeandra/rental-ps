<?php

namespace Database\Factories;

use App\Enums\StockMovementReason;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'created_by' => User::factory(),
            'reason' => fake()->randomElement(StockMovementReason::cases()),
            'quantity' => fake()->randomFloat(3, -100, 500),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Create an adjustment movement.
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => StockMovementReason::Adjustment,
        ]);
    }

    /**
     * Create a sale movement (negative qty).
     */
    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => StockMovementReason::Sale,
            'quantity' => -abs(fake()->randomFloat(3, 1, 50)),
        ]);
    }

    /**
     * Create a return movement (positive qty).
     */
    public function return(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => StockMovementReason::Return,
            'quantity' => abs(fake()->randomFloat(3, 1, 20)),
        ]);
    }

    /**
     * Create a transfer movement.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => StockMovementReason::Transfer,
        ]);
    }

    /**
     * Create an opname (stock take) movement.
     */
    public function opname(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => StockMovementReason::Opname,
        ]);
    }

    /**
     * Set specific warehouse.
     */
    public function forWarehouse(Warehouse $warehouse): static
    {
        return $this->state(fn (array $attributes) => [
            'warehouse_id' => $warehouse->id,
        ]);
    }

    /**
     * Set specific inventory item.
     */
    public function forInventoryItem(InventoryItem $item): static
    {
        return $this->state(fn (array $attributes) => [
            'inventory_item_id' => $item->id,
        ]);
    }

    /**
     * Set specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Set a positive quantity (stock in).
     */
    public function stockIn(?float $qty = null): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $qty ?? abs(fake()->randomFloat(3, 10, 200)),
        ]);
    }

    /**
     * Set a negative quantity (stock out).
     */
    public function stockOut(?float $qty = null): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => -abs($qty ?? fake()->randomFloat(3, 1, 50)),
        ]);
    }
}
