<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Search\Domain\Models\SearchSynonym;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class SearchSynonymController extends Controller
{
    public function index(): View
    {
        $synonyms = SearchSynonym::orderBy('term')->paginate(30);

        return view('admin::search.synonyms', compact('synonyms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'term' => ['required', 'string', 'max:64', 'unique:search_synonyms,term'],
            'synonyms' => ['required', 'string', 'max:500'],
        ]);

        $list = array_values(array_filter(array_map('trim', explode(',', $data['synonyms']))));

        SearchSynonym::create([
            'term' => strtolower(trim($data['term'])),
            'synonyms' => $list,
        ]);

        Artisan::call('search:sync-synonyms');

        return back()->with('success', 'Synonyme ajouté.');
    }

    public function destroy(SearchSynonym $synonym): RedirectResponse
    {
        $synonym->delete();
        Artisan::call('search:sync-synonyms');

        return back()->with('success', 'Synonyme supprimé.');
    }
}
