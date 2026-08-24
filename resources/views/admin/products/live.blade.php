@extends('layouts.admin')

@section('heading', 'Live Products')

@section('content')
    <p class="text-sm text-gray-500 mb-6">Everything actually published on the storefront right now, grouped by category. This is a snapshot of what customers can see and buy — not the review queue (that's <a href="{{ route('admin.products.index') }}" class="text-farmtech-green font-medium">Staging Queue</a>).</p>

    @forelse ($industries as $industry)
        @php $categories = collect($industry->categories())->filter(fn ($c) => $grouped->has($c->value)); @endphp
        @if ($categories->isNotEmpty())
            <section class="mb-8">
                <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ $industry->label() }}</h2>

                @foreach ($categories as $category)
                    @php $products = $grouped->get($category->value); @endphp
                    <div class="bg-white border rounded-xl overflow-hidden mb-4">
                        <div class="px-4 py-2.5 bg-gray-50 border-b flex items-center justify-between">
                            <span class="font-medium text-sm text-gray-800">{{ $category->label() }}</span>
                            <span class="text-xs text-gray-400">{{ $products->count() }} live</span>
                        </div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y">
                                @foreach ($products as $product)
                                    <tr>
                                        <td class="px-4 py-3 w-12">
                                            <div class="w-10 h-10 bg-gray-100 rounded-md overflow-hidden flex-shrink-0">
                                                @if ($product->thumbnail)
                                                    <img src="{{ $product->thumbnail->url }}" class="object-cover w-full h-full" alt="">
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.products.show', $product) }}" class="font-medium text-gray-800 hover:text-farmtech-green transition">{{ $product->title }}</a>
                                            <div class="text-xs text-gray-400 font-mono">{{ $product->sku }}</div>
                                        </td>
                                        <td class="px-4 py-3 w-28">
                                            <x-badge :color="$product->published_via === 'auto' ? 'yellow' : 'green'">{{ $product->published_via === 'auto' ? 'AI Auto' : 'Manual' }}</x-badge>
                                        </td>
                                        <td class="px-4 py-3 w-32 font-mono">R{{ number_format($product->retail_price_zar ?? 0, 2) }}</td>
                                        <td class="px-4 py-3 text-right w-56 whitespace-nowrap">
                                            <a href="{{ route('products.show', $product) }}" target="_blank" class="text-gray-500 hover:text-gray-700 mr-3">View live →</a>
                                            <a href="{{ route('admin.products.show', $product) }}" class="text-farmtech-green font-medium mr-3">Edit</a>
                                            <form method="POST" action="{{ route('admin.products.archive', $product) }}" class="inline" onsubmit="return confirm('Remove &quot;{{ addslashes($product->title) }}&quot; from the store?');">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-700 font-medium">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </section>
        @endif
    @empty
    @endforelse

    @if ($grouped->isEmpty())
        <div class="bg-white border rounded-xl p-8 text-center text-gray-400">
            Nothing is live on the storefront yet — approve a listing from the <a href="{{ route('admin.products.index') }}" class="text-farmtech-green font-medium">Staging Queue</a> to publish it.
        </div>
    @endif
@endsection
