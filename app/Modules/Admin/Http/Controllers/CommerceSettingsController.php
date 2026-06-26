<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Commerce\Domain\Models\CommerceSetting;
use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CommerceSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin::commerce.settings', [
            'shippingFee' => CommerceSetting::shippingFeeMinor(),
            'deliveryCity' => config('commerce.delivery.city', 'Brazzaville'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shipping_fee_minor' => ['required', 'integer', 'min:0'],
        ]);

        CommerceSetting::setValue('shipping_fee_minor', (int) $data['shipping_fee_minor']);

        return back()->with('success', 'Paramètres commerce mis à jour.');
    }
}
