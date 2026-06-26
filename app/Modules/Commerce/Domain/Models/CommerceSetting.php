<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class CommerceSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = self::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function shippingFeeMinor(): int
    {
        $stored = self::getValue('shipping_fee_minor');

        return is_numeric($stored)
            ? (int) $stored
            : (int) config('commerce.delivery.fee_minor', 1500);
    }
}
