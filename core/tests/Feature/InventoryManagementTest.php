<?php

use App\Enums\StockMovementReason;
use App\Models\InventoryItem;
use App\Models\Partner;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Warehouse Management', function () {
    it('can list warehouses', function () {
        Warehouse::factory()->count(3)->create();

        $response = $this->get(route('warehouses.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('warehouses/Index')
            ->has('warehouses.data', 3)
        );
    });

    it('can create a warehouse', function () {
        $data = [
            'name' => 'Main Warehouse',
            'address' => '123 Storage Lane',
            'is_active' => true,
        ];

        $response = $this->post(route('warehouses.store'), $data);

        $response->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', [
            'name' => 'Main Warehouse',
            'address' => '123 Storage Lane',
        ]);
    });

    it('auto-generates warehouse code', function () {
        $warehouse = Warehouse::factory()->create(['name' => 'Test Warehouse']);

        expect($warehouse->code)->toStartWith('WH-');
        expect(strlen($warehouse->code))->toBe(7); // WH-XXXX
    });

    it('can update a warehouse', function () {
        $warehouse = Warehouse::factory()->create();

        $response = $this->put(route('warehouses.update', $warehouse), [
            'name' => 'Updated Name',
            'address' => 'New Address',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('warehouses.index'));
        expect($warehouse->fresh()->name)->toBe('Updated Name');
        expect($warehouse->fresh()->is_active)->toBeFalse();
    });

    it('can delete a warehouse', function () {
        $warehouse = Warehouse::factory()->create();

        $response = $this->delete(route('warehouses.destroy', $warehouse));

        $response->assertRedirect(route('warehouses.index'));
        $this->assertSoftDeleted('warehouses', ['id' => $warehouse->id]);
    });
});

describe('Inventory Item Management', function () {
    it('can list inventory items', function () {
        InventoryItem::factory()->count(3)->create();

        $response = $this->get(route('inventory-items.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('inventory-items/Index')
            ->has('inventoryItems.data', 3)
        );
    });

    it('can create an inventory item', function () {
        $owner = Partner::factory()->supplier()->create();

        $data = [
            'owner_id' => $owner->id,
            'name' => 'Test Component',
            'sku' => 'TST-001',
            'unit' => 'pcs',
            'cost' => 15000,
            'default_share_percent' => 10.5,
            'is_active' => true,
        ];

        $response = $this->post(route('inventory-items.store'), $data);

        $response->assertRedirect(route('inventory-items.index'));
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Test Component',
            'sku' => 'TST-001',
            'owner_id' => $owner->id,
        ]);
    });

    it('auto-generates inventory item sku when not provided', function () {
        $owner = Partner::factory()->supplier()->create();
        $item = InventoryItem::create([
            'owner_id' => $owner->id,
            'name' => 'Test Item',
            'unit' => 'pcs',
            'cost' => 1000,
            'is_active' => true,
        ]);

        expect($item->sku)->toStartWith('INV-');
        expect(strlen($item->sku))->toBe(8); // INV-XXXX
    });

    it('belongs to an owner', function () {
        $owner = Partner::factory()->supplier()->create();
        $item = InventoryItem::factory()->forOwner($owner)->create();

        expect($item->owner->id)->toBe($owner->id);
    });
});

describe('Stock Movement Management', function () {
    it('can list stock movements', function () {
        $warehouse = Warehouse::factory()->create();
        $item = InventoryItem::factory()->create();
        StockMovement::factory()
            ->forWarehouse($warehouse)
            ->forInventoryItem($item)
            ->byUser($this->user)
            ->count(3)
            ->create();

        $response = $this->get(route('stock-movements.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('stock-movements/Index')
            ->has('movements.data', 3)
        );
    });

    it('can create a stock movement', function () {
        $warehouse = Warehouse::factory()->create();
        $item = InventoryItem::factory()->create();

        $data = [
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'reason' => StockMovementReason::Adjustment->value,
            'quantity' => 100,
            'notes' => 'Initial stock',
        ];

        $response = $this->post(route('stock-movements.store'), $data);

        $response->assertRedirect(route('stock-movements.index'));
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'quantity' => 100,
        ]);
    });

    it('updates stock level when stock movement is created', function () {
        $warehouse = Warehouse::factory()->create();
        $item = InventoryItem::factory()->create();

        // Create first movement (stock in: +50)
        StockMovement::factory()
            ->forWarehouse($warehouse)
            ->forInventoryItem($item)
            ->byUser($this->user)
            ->adjustment()
            ->state(['quantity' => 50])
            ->create();

        $stockLevel = StockLevel::where('warehouse_id', $warehouse->id)
            ->where('inventory_item_id', $item->id)
            ->first();

        expect($stockLevel)->not->toBeNull();
        expect((float) $stockLevel->qty_on_hand)->toBe(50.0);

        // Create second movement (stock out: -20)
        $secondMovement = StockMovement::factory()
            ->forWarehouse($warehouse)
            ->forInventoryItem($item)
            ->byUser($this->user)
            ->sale()
            ->state(['quantity' => -20])
            ->create();

        // Verify second movement was created with correct quantity
        expect((float) $secondMovement->quantity)->toBe(-20.0);

        // Check if duplicate stock levels were created
        $levelCount = StockLevel::where('warehouse_id', $warehouse->id)
            ->where('inventory_item_id', $item->id)
            ->count();
        expect($levelCount)->toBe(1, 'Should only have one stock level record');

        // Query stock level again since it has a composite key
        $updatedStockLevel = StockLevel::where('warehouse_id', $warehouse->id)
            ->where('inventory_item_id', $item->id)
            ->first();

        expect((float) $updatedStockLevel->qty_on_hand)->toBe(30.0);
    });

    it('allows negative stock levels with warning', function () {
        $warehouse = Warehouse::factory()->create();
        $item = InventoryItem::factory()->create();

        $data = [
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'reason' => StockMovementReason::Sale->value,
            'quantity' => -100,
            'notes' => 'Overselling',
        ];

        $response = $this->post(route('stock-movements.store'), $data);

        $response->assertRedirect(route('stock-movements.index'));
        $response->assertSessionHas('warning');

        $stockLevel = StockLevel::where('warehouse_id', $warehouse->id)
            ->where('inventory_item_id', $item->id)
            ->first();

        expect((float) $stockLevel->qty_on_hand)->toBe(-100.0);
    });
});

describe('Stock Level Management', function () {
    it('can list stock levels', function () {
        $warehouse = Warehouse::factory()->create();
        $item = InventoryItem::factory()->create();

        StockLevel::create([
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'qty_on_hand' => 100,
        ]);

        $response = $this->get(route('stock-levels.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('stock-levels/Index')
            ->has('stockLevels.data', 1)
        );
    });

    it('shows stock levels grouped by warehouse and item', function () {
        $warehouse1 = Warehouse::factory()->create(['name' => 'Warehouse A']);
        $warehouse2 = Warehouse::factory()->create(['name' => 'Warehouse B']);
        $item = InventoryItem::factory()->create();

        StockLevel::create([
            'warehouse_id' => $warehouse1->id,
            'inventory_item_id' => $item->id,
            'qty_on_hand' => 50,
        ]);

        StockLevel::create([
            'warehouse_id' => $warehouse2->id,
            'inventory_item_id' => $item->id,
            'qty_on_hand' => 75,
        ]);

        $response = $this->get(route('stock-levels.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('stock-levels/Index')
            ->has('stockLevels.data', 2)
        );
    });
});
