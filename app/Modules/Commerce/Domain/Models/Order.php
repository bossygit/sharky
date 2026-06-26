<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'uuid',
        'order_number',
        'user_id',
        'customer_email',
        'customer_name',
        'customer_phone',
        'delivery_address_text',
        'delivery_city',
        'status',
        'payment_method',
        'payment_status',
        'subtotal_minor',
        'shipping_minor',
        'total_minor',
        'currency_code',
        'customer_notes',
        'placed_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_minor' => 'integer',
            'shipping_minor' => 'integer',
            'total_minor' => 'integer',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->uuid)) {
                $order->uuid = (string) Str::uuid();
            }
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function formattedTotal(): string
    {
        return number_format($this->total_minor, 0, ',', ' ').' '.$this->currency_code;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function markPaid(): void
    {
        $this->payment_status = 'paid';
        $this->paid_at = now();
        if ($this->status === 'pending' || $this->status === 'awaiting_payment') {
            $this->status = 'paid';
        }
        $this->save();
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'NAS-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }
}
