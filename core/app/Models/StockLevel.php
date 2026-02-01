<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'inventory_item_id',
        'warehouse_id',
        'qty_on_hand',
        'qty_reserved',
        'min_threshold',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'decimal:0',
            'qty_reserved' => 'decimal:0',
            'min_threshold' => 'decimal:0',
        ];
    }

    /**
     * Get the inventory item for this stock level.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the warehouse for this stock level.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Check if stock is below minimum threshold.
     */
    public function isBelowThreshold(): bool
    {
        return $this->qty_on_hand < $this->min_threshold;
    }

    /**
     * Check if stock is negative.
     */
    public function isNegative(): bool
    {
        return $this->qty_on_hand < 0;
    }

    /**
     * Get available quantity (on hand minus reserved).
     */
    public function getAvailableQtyAttribute(): float
    {
        return (float) $this->qty_on_hand - (float) $this->qty_reserved;
    }
}
