@extends('layouts.admin')

@section('title', 'Video Outreach — Farmtech')
@section('heading', 'Supplier Video Outreach')

@section('content')
    <p class="text-sm text-gray-600 mb-4">
        {{ $rows->count() }} live product{{ $rows->count() === 1 ? '' : 's' }} (of {{ $totalLive }}) still have
        <strong>no listing video and no supplier WhatsApp number</strong> on file. For each one: copy the bilingual
        video-request script, open the real Alibaba listing to send it yourself, then paste whatever comes back —
        a WhatsApp number or a video link — straight into the row. Nothing is sent automatically.
    </p>

    @if ($rows->isEmpty())
        <div class="bg-white border rounded-xl p-8 text-center text-sm text-gray-500">
            Every live product already has a listing video or a supplier WhatsApp number. Nothing to chase.
        </div>
    @else
        <div class="bg-white border rounded-xl overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Chat &amp; Request Video</th>
                        <th class="px-4 py-3 w-72">Paste the reply (number or video link)</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($rows as $row)
                        <tr x-data="{
                                script: {{ Js::from($row->script) }},
                                listingUrl: {{ Js::from($row->listing_url) }},
                                copied: false,
                                async chatAndRequest() {
                                    try { await navigator.clipboard.writeText(this.script); this.copied = true; setTimeout(() => this.copied = false, 2500); }
                                    catch (e) { window.prompt('Copy this script manually (Ctrl/Cmd+C, then Enter):', this.script); }
                                    if (this.listingUrl) { window.open(this.listingUrl, '_blank', 'noopener'); }
                                },
                            }">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.products.show', $row->product) }}" class="font-medium text-gray-900 hover:text-farmtech-green transition">{{ $row->product->title }}</a>
                                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $row->product->sku }} &middot; {{ $row->product->category_label }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $row->product->supplier_name ?: '—' }}
                                @if ($row->listing_url)
                                    <a href="{{ $row->listing_url }}" target="_blank" rel="noopener" class="block text-xs text-orange-700 hover:text-orange-800 mt-0.5">View listing ↗</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" @click="chatAndRequest()"
                                        class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-1.5 rounded-full transition disabled:opacity-40 disabled:cursor-not-allowed"
                                        @if (! $row->listing_url) disabled title="No listing URL on file — copies the script only" @endif>
                                    <span x-show="!copied">💬 Chat &amp; Request Video</span>
                                    <span x-show="copied" x-cloak>✓ Script copied — listing opened</span>
                                </button>
                                @unless ($row->listing_url)
                                    <button type="button" @click="chatAndRequest()" class="block text-[11px] text-gray-400 hover:text-gray-600 mt-1 underline">Copy script only</button>
                                @endunless
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.outreach.update', $row->product) }}" class="flex items-center gap-1.5">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="reply" required
                                           placeholder="+86… or https://…mp4"
                                           class="w-full text-xs border-gray-300 rounded-md shadow-sm font-mono">
                                    <button type="submit" class="flex-shrink-0 text-xs font-semibold text-emerald-700 border border-emerald-300 hover:bg-emerald-50 rounded-md px-2.5 py-1.5 transition">Save</button>
                                </form>
                                @error('reply')
                                    <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <p class="text-xs text-gray-400 mt-4">
        A pasted value starting <code class="bg-gray-100 px-1 py-0.5 rounded">http://</code> /
        <code class="bg-gray-100 px-1 py-0.5 rounded">https://</code> is saved as the product's
        <code class="bg-gray-100 px-1 py-0.5 rounded">video_url</code>; anything else is saved as
        <code class="bg-gray-100 px-1 py-0.5 rounded">supplier_whatsapp</code>. Either way the row leaves this list.
    </p>
@endsection
