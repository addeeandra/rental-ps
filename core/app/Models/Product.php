<?php

namespace App\Models;

use App\Enums\RentalDuration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'category_id',
        'sales_price',
        'rental_price',
        'uom',
        'rental_duration',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rental_duration' => RentalDuration::class,
            'sales_price' => 'decimal:2',
            'rental_price' => 'decimal:2',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = static::generateCode();
            }
        });
    }

    /**
     * Generate a unique product code.
     */
    protected static function generateCode(): string
    {
        $lastProduct = static::withTrashed()
            ->where('code', 'like', 'PRD-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS INTEGER) DESC')
            ->first();

        if ($lastProduct) {
            preg_match('/PRD-(\d+)/', $lastProduct->code, $matches);
            $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $number = 1;
        }

        return 'PRD-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
