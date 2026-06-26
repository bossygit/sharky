<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Contracts;

use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Http\Request;

interface PaymentProvider
{
    public function code(): string;

    public function label(): string;

    public function isEnabled(): bool;

    /** @return array{status:string,provider_reference:?string,instructions:?string,payload:array} */
    public function initiate(Order $order): array;

    /** @return array{handled:bool,status:?string,message:?string} */
    public function handleWebhook(Request $request): array;
}
