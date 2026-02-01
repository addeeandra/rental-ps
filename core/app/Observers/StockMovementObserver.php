<?php

namespace App\Observers;

use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockMovementObserver
{
    /**
     * Handle the StockMovement "created" event.
     *
     * Update the stock level for the inventory item in the specified warehouse.
     * This allows negative stock values with a warning.
     */
    public function created(StockMovement $stockMovement): void
    {
        // Use DB query to handle the composite primary key properly
        $currentLevel = StockLevel::where('inventory_item_id', $stockMovement->inventory_item_id)
            ->where('warehouse_id', $stockMovement->warehouse_id)
            ->first();

        $newQty = ((float) ($currentLevel?->qty_on_hand ?? 0)) + (float) $stockMovement->quantity;

        if ($currentLevel) {
            // Update using query builder to avoid Eloquent's issues with composite keys
            DB::table('stock_levels')
                ->where('inventory_item_id', $stockMovement->inventory_item_id)
                ->where('warehouse_id', $stockMovement->warehouse_id)
                ->update([
                    'qty_on_hand' => $newQty,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('stock_levels')->insert([
                'inventory_item_id' => $stockMovement->inventory_item_id,
                'warehouse_id' => $stockMovement->warehouse_id,
                'qty_on_hand' => $newQty,
                'qty_reserved' => 0,
                'min_threshold' => 0,
                'updated_at' => now(),
            ]);
        }
    }
}
