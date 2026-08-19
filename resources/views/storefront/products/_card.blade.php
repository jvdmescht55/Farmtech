<a href="{{ route('products.show', $product) }}" class="bg-white border rounded-xl overflow-hidden hover:shadow-md transition">
    <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
        @if ($product->thumbnail)
            <img src="{{ $product->thumbnail->url }}" alt="{{ $product->title }}" class="object-cover w-full h-full">
        @else
            <span class="text-gray-400 text-sm">No image</span>
        @endif
    </div>
    <div class="p-4">
        <p class="text-xs uppercase tracking-wide text-farmtech-gold font-semibold">{{ $product->category_label }}</p>
        <h3 class="font-semibold text-sm mt-1 line-clamp-2">{{ $product->title }}</h3>
        <p class="mt-2 font-bold text-farmtech-green-dark">R{{ number_format($product->retail_price_zar, 2) }}</p>
        <p class="text-xs text-gray-500">incl. VAT · {{ $product->lead_time_days }}</p>
    </div>
</a>
