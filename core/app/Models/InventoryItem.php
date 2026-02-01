<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'owner_id',
        'unit',
        'cost',
        'default_share_percent',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'default_share_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->sku)) {
                $item->sku = static::generateSku();
            }
        });
    }

    /**
     * Generate a unique SKU.
     */
    protected static function generateSku(): string
    {
        $lastItem = static::withTrashed()
            ->where('sku', 'like', 'INV-%')
            ->orderByRaw('CAST(SUBSTRING(sku, 5) AS INTEGER) DESC')
            ->first();

        if ($lastItem) {
            preg_match('/INV-(\d+)/', $lastItem->sku, $matches);
            $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $number = 1;
        }

        return 'INV-'.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the owner (supplier) of this inventory item.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'owner_id');
    }

    /**
     * Get the stock levels for this inventory item.
     */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * Get the stock movements for this inventory item.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the products that use this inventory item as a component.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_components')
            ->withPivot(['slot', 'qty_per_product'])
            ->withTimestamps();
    }

    /**
     * Get the product components for this inventory item.
     */
    public function productComponents(): HasMany
    {
        return $this->hasMany(ProductComponent::class);
    }

    /**
     * Get total stock across all warehouses.
     */
    public function getTotalStockAttribute(): float
    {
        return (float) $this->stockLevels()->sum('qty_on_hand');
    }
}
