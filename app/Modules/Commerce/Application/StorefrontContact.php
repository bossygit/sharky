<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Application;

use App\Modules\Commerce\Domain\Models\CommerceSetting;

final class StorefrontContact
{
    public function __construct(
        public readonly string $phone,
        public readonly string $whatsapp,
        public readonly string $email,
    ) {}

    public static function current(): self
    {
        $raw = CommerceSetting::getValue('storefront_contact', []);
        if (! is_array($raw)) {
            $raw = [];
        }

        return new self(
            phone: trim((string) ($raw['phone'] ?? '')),
            whatsapp: self::digits((string) ($raw['whatsapp'] ?? '')),
            email: trim((string) ($raw['email'] ?? '')),
        );
    }

    public function telHref(): ?string
    {
        $digits = self::digits($this->phone);

        return $digits === '' ? null : 'tel:+'.$digits;
    }

    public function whatsappHref(?string $prefill = null): ?string
    {
        if ($this->whatsapp === '') {
            return null;
        }

        $url = 'https://wa.me/'.$this->whatsapp;
        if ($prefill !== null && $prefill !== '') {
            $url .= '?text='.rawurlencode($prefill);
        }

        return $url;
    }

    public function hasPhone(): bool
    {
        return $this->telHref() !== null;
    }

    public function hasWhatsapp(): bool
    {
        return $this->whatsapp !== '';
    }

    public function hasEmail(): bool
    {
        return $this->email !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private static function digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
