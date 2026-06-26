<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Infrastructure\Payments;

use App\Modules\Commerce\Contracts\PaymentProvider;
use InvalidArgumentException;

class PaymentManager
{
    /** @var array<string, PaymentProvider> */
    private array $providers;

    public function __construct()
    {
        $this->providers = [
            'cash_on_delivery' => new CashOnDeliveryProvider,
            'airtel_money' => new AirtelMoneyProvider,
            'mtn_momo' => new MtnMomoProvider,
        ];
    }

    public function get(string $code): PaymentProvider
    {
        if (! isset($this->providers[$code])) {
            throw new InvalidArgumentException("Provider de paiement inconnu : {$code}");
        }

        return $this->providers[$code];
    }

    /** @return list<PaymentProvider> */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->providers,
            fn (PaymentProvider $p) => $p->isEnabled(),
        ));
    }
}
