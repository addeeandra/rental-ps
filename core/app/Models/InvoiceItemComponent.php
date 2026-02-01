<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemComponent extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_item_id',
        'inventory_item_id',
        'warehouse_id',
        'owner_id',
        'qty',
        'share_percent',
        'share_amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'share_percent' => 'decimal:2',
            'share_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the invoice item that owns this component.
     */
    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    /**
     * Get the inventory item for this component.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the warehouse for this component.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the owner (supplier) for this component.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'owner_id');
    }
}
