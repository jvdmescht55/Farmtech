<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuggestionAdminController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.suggestions.index', [
            'suggestions' => Suggestion::with('user')
                ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
                ->when($request->input('kind'), fn ($q, $k) => $q->where('kind', $k))
                ->latest()->paginate(40)->withQueryString(),
            'counts' => Suggestion::groupBy('status')->selectRaw('status, count(*) c')->pluck('c', 'status'),
        ]);
    }

    public function update(Request $request, Suggestion $suggestion)
    {
        $suggestion->update($request->validate([
            'status' => ['required', Rule::in(array_keys(Suggestion::STATUSES))],
            'reply' => ['nullable', 'string', 'max:3000'],
        ]));

        return back()->with('status', 'Updated — the farmer sees this on their Suggestions page.');
    }
}
