<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Commerce\Application\StorefrontContact;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    public function contact(): View
    {
        return view('catalog::pages.contact', [
            'contact' => StorefrontContact::current(),
        ]);
    }

    public function about(): View
    {
        return view('catalog::pages.about', [
            'contact' => StorefrontContact::current(),
        ]);
    }

    public function terms(): View
    {
        return view('catalog::pages.terms', [
            'contact' => StorefrontContact::current(),
        ]);
    }

    public function returns(): View
    {
        return view('catalog::pages.returns', [
            'contact' => StorefrontContact::current(),
        ]);
    }
}
