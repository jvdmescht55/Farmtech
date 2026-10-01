<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreListing;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Farmtech's own device listings — write once, hit Publish, it's on /store. */
class ListingController extends Controller
{
    public function index()
    {
        return view('admin.listings.index', ['listings' => StoreListing::orderBy('sort')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.listings.form', ['listing' => new StoreListing(['features' => [], 'specs' => [], 'in_box' => []])]);
    }

    public function store(Request $request)
    {
        $listing = StoreListing::create($this->validated($request));

        return redirect()->route('admin.listings.edit', $listing)->with('status', 'Listing saved as a draft.');
    }

    public function edit(StoreListing $listing)
    {
        return view('admin.listings.form', ['listing' => $listing]);
    }

    public function update(Request $request, StoreListing $listing)
    {
        $listing->update($this->validated($request, $listing));

        return back()->with('status', 'Saved.');
    }

    public function toggle(StoreListing $listing)
    {
        $listing->update(['is_published' => ! $listing->is_published]);

        return back()->with('status', $listing->is_published ? "{$listing->name} is live on the store." : "{$listing->name} is hidden from the store.");
    }

    public function uploadImage(Request $request, StoreListing $listing)
    {
        $request->validate(['image' => ['required', 'image', 'max:8192']]);
        $path = $request->file('image')->store('listings', 'public');
        $listing->update(['images' => array_values(array_merge($listing->images ?? [], [$path]))]);

        return back()->with('status', 'Photo added.');
    }

    public function removeImage(Request $request, StoreListing $listing)
    {
        $listing->update(['images' => array_values(array_diff($listing->images ?? [], [$request->input('path')]))]);

        return back()->with('status', 'Photo removed.');
    }

    private function validated(Request $request, ?StoreListing $listing = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'alpha_dash', 'max:120', Rule::unique('store_listings')->ignore($listing?->id)],
            'tagline' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'availability' => ['nullable', 'string', 'max:160'],
            'module' => ['nullable', Rule::in(array_keys(config('herd.modules')))],
            'overview' => ['nullable', 'string', 'max:10000'],
            'features' => ['nullable', 'string', 'max:10000'],
            'specs' => ['nullable', 'string', 'max:10000'],
            'in_box' => ['nullable', 'string', 'max:5000'],
            'why' => ['nullable', 'string', 'max:5000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        // Simple text boxes in, structured lists out: "Title | body" and "Label | value" per line.
        $pairs = fn (?string $text, string $a, string $b) => collect(preg_split('/\R/', (string) $text))->map('trim')->filter()
            ->map(fn ($l) => array_combine([$a, $b], array_pad(array_map('trim', explode('|', $l, 2)), 2, '')))->values()->all();

        return [
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'tagline' => $data['tagline'] ?? null,
            'category' => $data['category'] ?? null,
            'price_cents' => isset($data['price']) && $data['price'] !== null ? (int) round($data['price'] * 100) : null,
            'availability' => $data['availability'] ?? null,
            'module' => $data['module'] ?? null,
            'overview' => $data['overview'] ?? null,
            'features' => $pairs($data['features'] ?? null, 'title', 'body'),
            'specs' => $pairs($data['specs'] ?? null, 'label', 'value'),
            'in_box' => collect(preg_split('/\R/', (string) ($data['in_box'] ?? '')))->map('trim')->filter()->values()->all(),
            'why' => $data['why'] ?? null,
            'sort' => $data['sort'] ?? 0,
        ];
    }
}
