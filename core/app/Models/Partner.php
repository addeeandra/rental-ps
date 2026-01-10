<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    /** @use HasFactory<\Database\Factories\PartnerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'type',
        'name',
        'phone',
        'mobile_phone',
        'email',
        'address_line_1',
        'address_line_2',
        'city',
        'province',
        'postal_code',
        'country',
        'gmap_url',
        'website',
        'notes',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($partner) {
            if (empty($partner->code)) {
                $partner->code = static::generateCode();
            }
        });
    }

    /**
     * Generate a unique partner code.
     */
    protected static function generateCode(): string
    {
        $lastPartner = static::withTrashed()
            ->where('code', 'like', 'P-%')
            ->orderByRaw('CAST(SUBSTRING(code, 3) AS INTEGER) DESC')
            ->first();

        if ($lastPartner) {
            preg_match('/P-(\d+)/', $lastPartner->code, $matches);
            $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $number = 1;
        }

        return 'P-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
