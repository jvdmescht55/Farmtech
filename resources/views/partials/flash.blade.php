@if (session('status'))
    <div class="mb-6 rounded-xl border border-hairline bg-white px-4 py-3 text-sm text-char flex gap-3"><span class="text-ochre">●</span>{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mb-6 rounded-xl border border-[#B0452F]/25 bg-[#B0452F]/5 px-4 py-3 text-sm text-[#B0452F]">
        @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif
