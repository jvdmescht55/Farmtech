@if (session('status'))
    <div class="mb-6 rounded-lg border border-mint/40 bg-mint/10 px-4 py-3 text-sm text-brand-950">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mb-6 rounded-lg border border-error/30 bg-error/5 px-4 py-3 text-sm text-error">
        @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif
