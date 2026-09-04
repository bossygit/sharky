<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Commerce\Application\StorefrontContact;
use App\Modules\Commerce\Domain\Models\CommerceSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CommerceSettingsController extends Controller
{
    public function edit(): View
    {
        $contact = StorefrontContact::current();

        return view('admin::commerce.settings', [
            'shippingFee' => CommerceSetting::shippingFeeMinor(),
            'deliveryCity' => config('commerce.delivery.city', 'Brazzaville'),
            'contactPhone' => $contact->phone,
            'contactWhatsapp' => $contact->whatsapp,
            'contactEmail' => $contact->email,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shipping_fee_minor' => ['required', 'integer', 'min:0'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'contact_whatsapp' => ['nullable', 'string', 'max:64'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        CommerceSetting::setValue('shipping_fee_minor', (int) $data['shipping_fee_minor']);
        CommerceSetting::setValue('storefront_contact', [
            'phone' => trim((string) ($data['contact_phone'] ?? '')),
            'whatsapp' => trim((string) ($data['contact_whatsapp'] ?? '')),
            'email' => trim((string) ($data['contact_email'] ?? '')),
        ]);

        return back()->with('success', 'Paramètres commerce mis à jour.');
    }
}
