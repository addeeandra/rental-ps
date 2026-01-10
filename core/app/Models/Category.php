<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->code)) {
                $category->code = static::generateCode();
            }
        });
    }

    /**
     * Generate a unique category code.
     */
    protected static function generateCode(): string
    {
        $lastCategory = static::withTrashed()
            ->where('code', 'like', 'CAT-%')
            ->orderByRaw('CAST(SUBSTRING(code, 5) AS INTEGER) DESC')
            ->first();

        if ($lastCategory) {
            preg_match('/CAT-(\d+)/', $lastCategory->code, $matches);
            $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $number = 1;
        }

        return 'CAT-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the products for the category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
