<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseStoreRequest;
use App\Http\Requests\WarehouseUpdateRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the warehouses.
     */
    public function index(Request $request): Response
    {
        $query = Warehouse::query()
            ->withCount(['stockLevels as total_items' => function ($q) {
                $q->where('qty_on_hand', '>', 0);
            }]);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Paginate and get warehouses
        $warehouses = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('warehouses/Index', [
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $request->input('search'),
                'is_active' => $request->input('is_active'),
            ],
        ]);
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(WarehouseStoreRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());

        return to_route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(WarehouseUpdateRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return to_route('warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        // Check if warehouse has any stock
        if ($warehouse->stockLevels()->where('qty_on_hand', '!=', 0)->exists()) {
            return to_route('warehouses.index')->with('error', 'Cannot delete warehouse with existing stock. Please transfer or adjust stock first.');
        }

        $warehouse->delete();

        return to_route('warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }
}
