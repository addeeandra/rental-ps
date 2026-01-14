<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\OrderType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_number',
        'reference_number',
        'partner_id',
        'invoice_date',
        'due_date',
        'order_type',
        'rental_start_date',
        'rental_end_date',
        'delivery_time',
        'return_time',
        'notes',
        'terms',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_fee',
        'total_amount',
        'paid_amount',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'rental_start_date' => 'date',
            'rental_end_date' => 'date',
            'order_type' => OrderType::class,
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
        });
    }

    /**
     * Generate a unique invoice number with collision handling.
     */
    protected static function generateInvoiceNumber(): string
    {
        $maxAttempts = 3;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                return DB::transaction(function () {
                    $year = date('Y');
                    $prefixCode = CompanySetting::current()->invoice_number_prefix;
                    $prefix = "{$prefixCode}-{$year}-";

                    $lastInvoice = static::withTrashed()
                        ->where('invoice_number', 'like', "{$prefix}%")
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(invoice_number, ' . (strlen($prefix) + 1) . ') AS INTEGER) DESC')
                        ->first();

                    if ($lastInvoice) {
                        preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $lastInvoice->invoice_number, $matches);
                        $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
                    } else {
                        $number = 1;
                    }

                    return sprintf('%s%04d', $prefix, $number);
                });
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                usleep(100000); // Wait 100ms before retry
            }
        }

        throw new \RuntimeException('Failed to generate unique invoice number after ' . $maxAttempts . ' attempts');
    }

    /**
     * Get the partner that owns the invoice.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the user who created the invoice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the invoice items.
     */
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /**
     * Get the balance (unpaid amount).
     */
    public function getBalanceAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->paid_amount;
    }

    /**
     * Check if the invoice is editable.
     */
    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, [InvoiceStatus::Unpaid, InvoiceStatus::Partial]);
    }

    /**
     * Get the rental duration in days.
     */
    public function getRentalDaysAttribute(): ?int
    {
        if ($this->rental_start_date && $this->rental_end_date) {
            return Carbon::parse($this->rental_start_date)->diffInDays(
                Carbon::parse($this->rental_end_date)
            ) + 1; // Include both start and end day
        }

        return null;
    }

    /**
     * Calculate and update the invoice totals.
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->invoiceItems()->sum('total');
        $this->total_amount = $this->subtotal - $this->discount_amount + $this->tax_amount + $this->shipping_fee;
    }

    /**
     * Update the invoice status based on paid amount.
     */
    public function updateStatus(): void
    {
        if ($this->paid_amount <= 0) {
            $this->status = InvoiceStatus::Unpaid;
        } elseif ($this->paid_amount >= $this->total_amount) {
            $this->status = InvoiceStatus::Paid;
        } else {
            $this->status = InvoiceStatus::Partial;
        }
    }
}
