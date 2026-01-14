<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CompanySetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'website',
        'logo_path',
        'tax_number',
        'invoice_number_prefix',
        'invoice_default_terms',
        'invoice_default_notes',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when settings are updated or deleted
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Get the current company settings (singleton pattern).
     */
    public static function current(): self
    {
        return Cache::tags(['settings'])->remember('company_settings', 86400, function () {
            $settings = static::firstOrCreate(
                ['id' => 1],
                [
                    'company_name' => 'My Company',
                    'invoice_number_prefix' => 'INV',
                ]
            );

            $settings->append('logo_url');

            return $settings;
        });
    }

    /**
     * Clear the cached company settings.
     */
    public static function clearCache(): void
    {
        Cache::tags(['settings'])->flush();
    }

    /**
     * Get the logo URL attribute.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? asset('storage/' . $this->logo_path)
            : null;
    }
}
