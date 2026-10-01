<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "What should we build for you?" — from signed-in farmers and from the public site. */
class SuggestionController extends Controller
{
    public function index(Request $request)
    {
        return view('herd.suggest', ['mine' => Suggestion::where('user_id', $request->user()->id)->latest()->get()]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(Suggestion::KINDS))],
            'title' => ['required', 'string', 'max:160'],
            'details' => ['nullable', 'string', 'max:5000'],
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:120'],
            'email' => [$user ? 'nullable' : 'required', 'email', 'max:190'],
            'website' => ['prohibited'],
        ]);
        Suggestion::create($data + ['user_id' => $user?->id, 'name' => $data['name'] ?? $user?->name, 'email' => $data['email'] ?? $user?->email]);

        return back()->with('status', 'Lekker, thanks! We read every one — you\'ll see the status change here as we work on it.')->with('suggest_ok', true);
    }
}
