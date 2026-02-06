<?php

use App\Enums\StockMovementReason;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('creates stock movements when creating invoice with components', function () {
    $client = Partner::factory()->create(['type' => 'Client']);
    $supplier = Partner::factory()->create(['type' => 'Supplier']);
    $warehouse = Warehouse::factory()->create();
    $inventoryItem = InventoryItem::factory()->create([
        'owner_id' => $supplier->id,
        'default_share_percent' => 50,
    ]);

    // Create initial stock
    StockMovement::factory()->create([
        'inventory_item_id' => $inventoryItem->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 100,
        'reason' => StockMovementReason::Adjustment,
    ]);

    $response = $this->postJson('/invoices', [
        'partner_id' => $client->id,
        'invoice_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'order_type' => 'sales',
        'line_items' => [
            [
                'product_id' => null,
                'description' => 'Test Item',
                'quantity' => 1,
                'unit_price' => 100,
                'components' => [
                    [
                        'inventory_item_id' => $inventoryItem->id,
                        'warehouse_id' => $warehouse->id,
                        'qty' => 5,
                        'share_percent' => null, // Will use default
                    ],
                ],
            ],
        ],
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_fee' => 0,
    ]);

    $response->assertStatus(302);

    $invoice = Invoice::latest()->first();
    expect($invoice)->not->toBeNull();

    $invoiceItem = $invoice->invoiceItems->first();
    expect($invoiceItem->invoiceItemComponents)->toHaveCount(1);

    $component = $invoiceItem->invoiceItemComponents->first();
    expect($component->inventory_item_id)->toBe($inventoryItem->id);
    expect($component->qty)->toBe('5.00');
    expect($component->share_percent)->toBe('50.00');
    expect($component->share_amount)->toBe('50.00'); // 50% of 100

    // Check stock movement was created
    $stockMovement = StockMovement::where('ref_type', get_class($invoiceItem))
        ->where('ref_id', $invoiceItem->id)
        ->first();

    expect($stockMovement)->not->toBeNull();
    expect($stockMovement->quantity)->toBe('-5.00'); // Negative for deduction
    expect($stockMovement->reason)->toBe(StockMovementReason::Sale);
});

it('reverses stock movements when updating invoice', function () {
    $client = Partner::factory()->create(['type' => 'Client']);
    $supplier = Partner::factory()->create(['type' => 'Supplier']);
    $warehouse = Warehouse::factory()->create();
    $inventoryItem = InventoryItem::factory()->create([
        'owner_id' => $supplier->id,
        'default_share_percent' => 50,
    ]);

    // Create initial stock
    StockMovement::factory()->create([
        'inventory_item_id' => $inventoryItem->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 100,
        'reason' => StockMovementReason::Adjustment,
    ]);

    // Create invoice with component
    $invoice = Invoice::factory()->create([
        'partner_id' => $client->id,
        'order_type' => 'sales',
    ]);

    $invoiceItem = $invoice->invoiceItems()->create([
        'description' => 'Test Item',
        'quantity' => 1,
        'unit_price' => 100,
        'total' => 100,
        'sort_order' => 0,
    ]);

    $invoiceItem->invoiceItemComponents()->create([
        'inventory_item_id' => $inventoryItem->id,
        'warehouse_id' => $warehouse->id,
        'owner_id' => $supplier->id,
        'qty' => 5,
        'share_percent' => 50,
        'share_amount' => 50,
    ]);

    StockMovement::create([
        'inventory_item_id' => $inventoryItem->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => -5,
        'reason' => StockMovementReason::Sale,
        'ref_type' => get_class($invoiceItem),
        'ref_id' => $invoiceItem->id,
        'created_by' => $this->user->id,
    ]);

    $stockBeforeUpdate = StockMovement::where('inventory_item_id', $inventoryItem->id)->count();

    $response = $this->patchJson("/invoices/{$invoice->id}", [
        'partner_id' => $client->id,
        'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
        'due_date' => $invoice->due_date->format('Y-m-d'),
        'order_type' => 'sales',
        'line_items' => [
            [
                'product_id' => null,
                'description' => 'Test Item Updated',
                'quantity' => 1,
                'unit_price' => 100,
                'components' => [
                    [
                        'inventory_item_id' => $inventoryItem->id,
                        'warehouse_id' => $warehouse->id,
                        'qty' => 10, // Changed from 5 to 10
                        'share_percent' => null,
                    ],
                ],
            ],
        ],
        'discount_amount' => 0,
        'tax_amount' => 0,
        'shipping_fee' => 0,
    ]);

    $response->assertStatus(302);

    // Should have created a reverse movement and a new sale movement
    $stockAfterUpdate = StockMovement::where('inventory_item_id', $inventoryItem->id)->count();
    expect($stockAfterUpdate)->toBeGreaterThan($stockBeforeUpdate);

    // Check for adjustment (reverse) movement
    $reverseMovement = StockMovement::where('inventory_item_id', $inventoryItem->id)
        ->where('reason', StockMovementReason::Adjustment)
        ->where('quantity', 5) // Positive to restore
        ->latest()
        ->first();

    expect($reverseMovement)->not->toBeNull();

    // Check for new sale movement
    $newSaleMovement = StockMovement::where('inventory_item_id', $inventoryItem->id)
        ->where('reason', StockMovementReason::Sale)
        ->where('quantity', -10) // Negative for new deduction
        ->latest()
        ->first();

    expect($newSaleMovement)->not->toBeNull();
});

it('can update component share percentage', function () {
    $client = Partner::factory()->create(['type' => 'Client']);
    $supplier = Partner::factory()->create(['type' => 'Supplier']);
    $warehouse = Warehouse::factory()->create();
    $inventoryItem = InventoryItem::factory()->create([
        'owner_id' => $supplier->id,
        'default_share_percent' => 50,
    ]);

    $invoice = Invoice::factory()->create([
        'partner_id' => $client->id,
        'order_type' => 'sales',
    ]);

    $invoiceItem = $invoice->invoiceItems()->create([
        'description' => 'Test Item',
        'quantity' => 1,
        'unit_price' => 100,
        'total' => 100,
        'sort_order' => 0,
    ]);

    $component = $invoiceItem->invoiceItemComponents()->create([
        'inventory_item_id' => $inventoryItem->id,
        'warehouse_id' => $warehouse->id,
        'owner_id' => $supplier->id,
        'qty' => 5,
        'share_percent' => 50,
        'share_amount' => 50,
    ]);

    $response = $this->patchJson("/invoices/{$invoice->id}/components/{$component->id}/share", [
        'share_percent' => 75,
    ]);

    $response->assertStatus(302);

    $component->refresh();
    expect($component->share_percent)->toBe('75.00');
    expect($component->share_amount)->toBe('75.00'); // 75% of 100
});
