<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryItemStoreRequest;
use App\Http\Requests\InventoryItemUpdateRequest;
use App\Models\InventoryItem;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryItemController extends Controller
{
    /**
     * Display a listing of the inventory items.
     */
    public function index(Request $request): Response
    {
        $query = InventoryItem::query()
            ->with('owner:id,code,name')
            ->withSum('stockLevels as total_stock', 'qty_on_hand');

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by owner (supplier)
        if ($ownerId = $request->input('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Paginate and get items
        $inventoryItems = $query->latest()->paginate(20)->withQueryString();

        // Get suppliers for filter dropdown
        $suppliers = Partner::select('id', 'code', 'name')
            ->where('type', 'Supplier')
            ->orderBy('name')
            ->get();

        return Inertia::render('inventory-items/Index', [
            'inventoryItems' => $inventoryItems,
            'suppliers' => $suppliers,
            'filters' => [
                'search' => $request->input('search'),
                'owner_id' => $ownerId,
                'is_active' => $request->input('is_active'),
            ],
        ]);
    }

    /**
     * Store a newly created inventory item in storage.
     */
    public function store(InventoryItemStoreRequest $request): RedirectResponse
    {
        InventoryItem::create($request->validated());

        return to_route('inventory-items.index')->with('success', 'Inventory item created successfully.');
    }

    /**
     * Update the specified inventory item in storage.
     */
    public function update(InventoryItemUpdateRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $inventoryItem->update($request->validated());

        return to_route('inventory-items.index')->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Remove the specified inventory item from storage.
     */
    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        // Check if item has any stock
        if ($inventoryItem->stockLevels()->where('qty_on_hand', '!=', 0)->exists()) {
            return to_route('inventory-items.index')->with('error', 'Cannot delete inventory item with existing stock. Please adjust stock to zero first.');
        }

        // Check if item is used in any products
        if ($inventoryItem->productComponents()->exists()) {
            return to_route('inventory-items.index')->with('error', 'Cannot delete inventory item that is used in products. Please remove it from products first.');
        }

        $inventoryItem->delete();

        return to_route('inventory-items.index')->with('success', 'Inventory item deleted successfully.');
    }
}
