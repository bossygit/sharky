<?php

declare(strict_types=1);

return [
    'currency' => 'XAF',

    'delivery' => [
        'city' => env('COMMERCE_DELIVERY_CITY', 'Brazzaville'),
        'fee_minor' => (int) env('COMMERCE_SHIPPING_FEE', 1500),
    ],

    'otp' => [
        'ttl_minutes' => 10,
        'length' => 6,
    ],

    'payments' => [
        'cash_on_delivery' => [
            'enabled' => true,
            'label' => 'Paiement à la livraison',
        ],
        'airtel_money' => [
            'enabled' => env('AIRTEL_MONEY_ENABLED', true),
            'label' => 'Airtel Money',
            'merchant_code' => env('AIRTEL_MONEY_MERCHANT_CODE'),
            'webhook_secret' => env('AIRTEL_MONEY_WEBHOOK_SECRET'),
        ],
        'mtn_momo' => [
            'enabled' => env('MTN_MOMO_ENABLED', true),
            'label' => 'MTN Mobile Money',
            'subscription_key' => env('MTN_MOMO_SUBSCRIPTION_KEY'),
            'webhook_secret' => env('MTN_MOMO_WEBHOOK_SECRET'),
        ],
    ],
];
