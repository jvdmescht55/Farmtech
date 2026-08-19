@extends('layouts.storefront')

@section('title', $product->title.' — Farmtech')

@section('content')
    <div class="grid md:grid-cols-2 gap-10">
        <div>
            <div class="aspect-square bg-white border rounded-xl overflow-hidden flex items-center justify-center mb-3">
                @if ($product->images->isNotEmpty())
                    <img id="main-image" src="{{ $product->images->first()->url }}" alt="{{ $product->title }}" class="object-contain w-full h-full">
                @else
                    <span class="text-gray-400">No image available</span>
                @endif
            </div>
            @if ($product->images->count() > 1)
                <div class="grid grid-cols-5 gap-2">
                    @foreach ($product->images as $image)
                        <button type="button" onclick="document.getElementById('main-image').src = this.querySelector('img').src"
                                class="border rounded-lg overflow-hidden aspect-square">
                            <img src="{{ $image->url }}" alt="" class="object-cover w-full h-full">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <p class="text-xs uppercase tracking-wide text-farmtech-gold font-semibold">{{ $product->category_label }}</p>
            <h1 class="text-2xl font-bold mt-1">{{ $product->title }}</h1>
            <p class="text-gray-600 mt-3">{{ $product->short_description }}</p>

            <div class="mt-6 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-farmtech-green-dark">R{{ number_format($product->retail_price_zar, 2) }}</span>
                <span class="text-sm text-gray-500">incl. 15% VAT</span>
            </div>

            <div class="mt-2 text-sm">
                @if ($product->stock_status === 'in_stock')
                    <span class="text-green-700 font-medium">In Stock</span>
                @else
                    <span class="text-farmtech-gold font-medium">Pre-Order</span>
                @endif
                · Lead time: {{ $product->lead_time_days }}
            </div>

            <div class="mt-3 bg-farmtech-gold/20 border border-farmtech-gold text-farmtech-green-dark text-sm rounded-md px-3 py-2">
                Direct Express Delivery (7–12 Business Days) with All Customs &amp; Duties Handled
            </div>

            <form action="{{ route('cart.add', $product) }}" method="POST" class="mt-6 flex gap-3">
                @csrf
                <input type="number" name="quantity" value="1" min="1" class="w-20 border rounded-md px-3 py-2">
                <button type="submit" class="bg-farmtech-green text-white font-semibold px-6 py-2 rounded-md hover:bg-farmtech-green-dark">Add to Cart</button>
            </form>

            @if ($product->specs->isNotEmpty())
                <div class="mt-8">
                    <h2 class="font-semibold mb-3">Technical Specifications</h2>
                    @foreach ($product->specs->groupBy('spec_group') as $group => $specs)
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ $group }}</h3>
                            <table class="w-full text-sm border rounded-md overflow-hidden">
                                <tbody>
                                    @foreach ($specs as $spec)
                                        <tr class="border-t first:border-t-0 {{ $spec->is_highlight ? 'bg-farmtech-gold/10' : '' }}">
                                            <td class="px-3 py-2 font-medium text-gray-600 w-1/2">{{ $spec->spec_key }}</td>
                                            <td class="px-3 py-2">{{ $spec->spec_value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($product->description_html)
                <div class="mt-8 prose prose-sm max-w-none">
                    {!! $product->description_html !!}
                </div>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-16">
            <h2 class="text-xl font-bold mb-4">You Might Also Need</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach ($related as $item)
                    @include('storefront.products._card', ['product' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endsection
