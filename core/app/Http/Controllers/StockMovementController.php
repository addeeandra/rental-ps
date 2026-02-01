<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementReason;
use App\Http\Requests\StockMovementStoreRequest;
use App\Models\InventoryItem;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the stock movements.
     */
    public function index(Request $request): Response
    {
        $query = StockMovement::query()
            ->with([
                'inventoryItem:id,sku,name',
                'warehouse:id,code,name',
                'createdBy:id,name',
            ]);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('inventoryItem', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('warehouse', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by inventory item
        if ($inventoryItemId = $request->input('inventory_item_id')) {
            $query->where('inventory_item_id', $inventoryItemId);
        }

        // Filter by warehouse
        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Filter by reason
        if ($reason = $request->input('reason')) {
            $query->where('reason', $reason);
        }

        // Filter by date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Paginate and get movements
        $movements = $query->latest()->paginate(20)->withQueryString();

        // Get data for filter dropdowns
        $inventoryItems = InventoryItem::select('id', 'sku', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::select('id', 'code', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('stock-movements/Index', [
            'movements' => $movements,
            'inventoryItems' => $inventoryItems,
            'warehouses' => $warehouses,
            'reasons' => StockMovementReason::options(),
            'filters' => [
                'search' => $request->input('search'),
                'inventory_item_id' => $inventoryItemId,
                'warehouse_id' => $warehouseId,
                'reason' => $reason,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    /**
     * Store a newly created stock movement in storage.
     */
    public function store(StockMovementStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        // Check current stock level for warning
        $currentStock = StockLevel::where('inventory_item_id', $data['inventory_item_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->first();

        $currentQty = $currentStock ? (float) $currentStock->qty_on_hand : 0;
        $newQty = $currentQty + (float) $data['quantity'];

        // Create the movement (observer will update stock level)
        StockMovement::create($data);

        // Return with warning if stock goes negative
        if ($newQty < 0) {
            return to_route('stock-movements.index')->with('warning', "Stock movement recorded. Warning: Stock is now negative ({$newQty}).");
        }

        return to_route('stock-movements.index')->with('success', 'Stock movement recorded successfully.');
    }
}
