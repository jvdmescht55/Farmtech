@extends('layouts.admin')

@section('heading', 'Supplier Outreach')

@section('content')
    <p class="text-sm text-gray-600 mb-4">
        Top {{ $rows->count() }} highest-margin, value-dense, problem-solving products (see
        <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">App\Services\SupplierOutreachCurator</code>) —
        {{ $withPhoneCount }}/{{ $rows->count() }} have a supplier WhatsApp number on file.
        Add one on a product's edit page to turn its row from an Alibaba-only fallback into a direct 1-click link.
    </p>

    <div class="bg-white border rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Rank</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Target Problem</th>
                    <th class="px-4 py-3">Alibaba Chat Link</th>
                    <th class="px-4 py-3">WhatsApp Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($rows as $row)
                    <tr x-data="{
                            message: {{ Js::from($row->message) }},
                            copied: false,
                            async copyMessage() {
                                try { await navigator.clipboard.writeText(this.message); this.copied = true; setTimeout(() => this.copied = false, 2000); }
                                catch (e) { window.prompt('Copy this message manually (Ctrl/Cmd+C, then Enter):', this.message); }
                            },
                        }">
                        <td class="px-4 py-3 font-mono text-gray-500">{{ $row->rank }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.products.show', $row->product) }}" class="font-medium text-gray-900 hover:text-farmtech-green transition">{{ $row->product->title }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $row->target_problem }}</td>
                        <td class="px-4 py-3">
                            @if ($row->alibaba_url)
                                <a href="{{ $row->alibaba_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-orange-700 hover:text-orange-800 font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    Open listing
                                </a>
                            @else
                                <span class="text-gray-400">— none on file —</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($row->whatsapp_url)
                                <a href="{{ $row->whatsapp_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-1.5 rounded-full transition">
                                    💬 Direct Link ({{ $row->phone }})
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-800 font-semibold text-xs px-3 py-1.5 rounded-full">Need Phone</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" @click="copyMessage()" class="inline-flex items-center gap-1.5 border border-gray-300 hover:border-emerald-400 hover:bg-emerald-50 text-gray-700 font-medium text-xs px-3 py-1.5 rounded-full transition whitespace-nowrap">
                                <span x-show="!copied">📋 Copy Message</span>
                                <span x-show="copied" x-cloak class="text-emerald-700">✓ Copied</span>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-4">
        Same data as <code class="bg-gray-100 px-1 py-0.5 rounded">php artisan suppliers:outreach-list</code> —
        run that in a terminal for a copy-pasteable, full-text version of every message.
    </p>
@endsection
