@extends('layouts.admin')

@section('heading', 'Staging Queue')

@section('content')
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <select name="status" onchange="this.form.submit()" class="border rounded-md px-3 py-2 text-sm">
            @foreach (['pending_review' => 'Pending Review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'rejected_uncompetitive' => 'Rejected (Uncompetitive)', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? 'pending_review') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="category" onchange="this.form.submit()" class="border rounded-md px-3 py-2 text-sm">
            <option value="">All Categories</option>
            @foreach (\App\Enums\ProductCategory::cases() as $category)
                <option value="{{ $category->value }}" @selected(($filters['category'] ?? '') === $category->value)>{{ $category->shortLabel() }}</option>
            @endforeach
        </select>

        <select name="verdict" onchange="this.form.submit()" class="border rounded-md px-3 py-2 text-sm">
            <option value="">Any Verdict</option>
            @foreach (['PASS', 'WARN', 'FAIL'] as $verdict)
                <option value="{{ $verdict }}" @selected(($filters['verdict'] ?? '') === $verdict)>{{ $verdict }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white border rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Supplier</th>
                    <th class="px-4 py-3">Verdict</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Risk</th>
                    <th class="px-4 py-3">Retail (ZAR)</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-3 flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-100 rounded-md overflow-hidden flex-shrink-0">
                                @if ($product->thumbnail)
                                    <img src="{{ $product->thumbnail->url }}" class="object-cover w-full h-full" alt="">
                                @endif
                            </div>
                            <span class="font-medium">{{ $product->title }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $product->category->shortLabel() }}</td>
                        <td class="px-4 py-3">{{ $product->complianceAudit?->supplier_name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($verdict = $product->complianceAudit?->audit_verdict)
                                <x-badge :color="$verdict === 'PASS' ? 'green' : ($verdict === 'WARN' ? 'yellow' : 'red')">{{ $verdict }}</x-badge>
                            @else
                                <x-badge color="gray">N/A</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($product->status === 'rejected_uncompetitive')
                                <x-badge color="yellow">Uncompetitive</x-badge>
                            @elseif ($product->status === 'rejected')
                                <x-badge color="red">Rejected</x-badge>
                            @else
                                <x-badge color="gray">{{ \Illuminate\Support\Str::headline($product->status) }}</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $product->complianceAudit?->risk_score ?? '—' }}</td>
                        <td class="px-4 py-3">R{{ number_format($product->retail_price_zar ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.products.show', $product) }}" class="text-farmtech-green font-medium">Review →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No products match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $products->links() }}</div>
@endsection
