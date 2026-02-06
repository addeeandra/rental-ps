<?php

use App\Models\InventoryItem;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('can create a product with components', function () {
    $owner = Partner::factory()->create(['type' => 'Supplier']);
    $inventoryItem1 = InventoryItem::factory()->create(['owner_id' => $owner->id]);
    $inventoryItem2 = InventoryItem::factory()->create(['owner_id' => $owner->id]);

    $category = \App\Models\Category::factory()->create();

    $response = $this->postJson('/products', [
        'code' => 'PROD-001',
        'name' => 'Test Product',
        'category_id' => $category->id,
        'sales_price' => 100,
        'rental_price' => 10,
        'uom' => 'pcs',
        'rental_duration' => 'day',
        'components' => [
            [
                'slot' => 1,
                'inventory_item_id' => $inventoryItem1->id,
                'qty_per_product' => 2,
            ],
            [
                'slot' => 2,
                'inventory_item_id' => $inventoryItem2->id,
                'qty_per_product' => 1,
            ],
        ],
    ]);

    $response->assertStatus(302);

    $product = Product::where('code', 'PROD-001')->first();
    expect($product)->not->toBeNull();
    expect($product->productComponents)->toHaveCount(2);
    expect($product->productComponents[0]->slot)->toBe(1);
    expect($product->productComponents[0]->qty_per_product)->toBe('2.00');
});

it('can update product components', function () {
    $owner = Partner::factory()->create(['type' => 'Supplier']);
    $inventoryItem = InventoryItem::factory()->create(['owner_id' => $owner->id]);

    $product = Product::factory()->create();

    $response = $this->patchJson("/products/{$product->id}", [
        'code' => $product->code,
        'name' => $product->name,
        'category_id' => $product->category_id,
        'sales_price' => $product->sales_price,
        'rental_price' => $product->rental_price,
        'uom' => $product->uom,
        'rental_duration' => $product->rental_duration,
        'components' => [
            [
                'slot' => 1,
                'inventory_item_id' => $inventoryItem->id,
                'qty_per_product' => 3,
            ],
        ],
    ]);

    $response->assertStatus(302);

    $product->refresh();
    expect($product->productComponents)->toHaveCount(1);
    expect($product->productComponents[0]->qty_per_product)->toBe('3.00');
});
