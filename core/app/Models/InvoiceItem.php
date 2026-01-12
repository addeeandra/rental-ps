<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($item) {
            if (!isset($item->total)) {
                $item->total = $item->quantity * $item->unit_price;
            }
        });

        static::updating(function ($item) {
            $item->total = $item->quantity * $item->unit_price;
        });
    }

    /**
     * Get the invoice that owns the item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the product associated with the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the display description (prefer product name if available).
     */
    public function getDisplayDescriptionAttribute(): string
    {
        if ($this->product_id && $this->product) {
            return $this->product->name;
        }

        return $this->description;
    }
}
