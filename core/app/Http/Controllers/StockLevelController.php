<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockLevelController extends Controller
{
    /**
     * Display a listing of the stock levels.
     */
    public function index(Request $request): Response
    {
        $query = StockLevel::query()
            ->with([
                'inventoryItem:id,sku,name,owner_id',
                'inventoryItem.owner:id,code,name',
                'warehouse:id,code,name',
            ]);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('inventoryItem', function ($q) use ($search) {
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

        // Filter by stock status
        if ($status = $request->input('status')) {
            switch ($status) {
                case 'negative':
                    $query->where('qty_on_hand', '<', 0);
                    break;
                case 'low':
                    $query->whereColumn('qty_on_hand', '<', 'min_threshold')
                        ->where('qty_on_hand', '>=', 0);
                    break;
                case 'ok':
                    $query->whereColumn('qty_on_hand', '>=', 'min_threshold');
                    break;
            }
        }

        // Paginate and get stock levels
        $stockLevels = $query->orderBy('inventory_item_id')
            ->orderBy('warehouse_id')
            ->paginate(20)
            ->withQueryString();

        // Get data for filter dropdowns
        $inventoryItems = InventoryItem::select('id', 'sku', 'name')
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('stock-levels/Index', [
            'stockLevels' => $stockLevels,
            'inventoryItems' => $inventoryItems,
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $request->input('search'),
                'inventory_item_id' => $inventoryItemId,
                'warehouse_id' => $warehouseId,
                'status' => $status,
            ],
        ]);
    }
}
