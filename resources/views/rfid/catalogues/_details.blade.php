<div class="grid sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2"><label class="app-label">Title</label><input name="title" value="{{ old('title', $c->title) }}" required placeholder="Kenhardt Meatmaster Production Sale 2026" class="app-input"></div>
    <div><label class="app-label">Section</label>
        <select name="section" class="app-input">@foreach (\App\Models\SaleCatalogue::SECTIONS as $s)<option @selected(old('section', $c->section)===$s)>{{ $s }}</option>@endforeach</select></div>
    <div><label class="app-label">Breed</label><input name="breed" value="{{ old('breed', $c->breed ?? auth()->user()->breed) }}" placeholder="Meatmaster" class="app-input"></div>
    <div><label class="app-label">Sale date</label><input type="date" name="sale_date" value="{{ old('sale_date', $c->sale_date?->toDateString()) }}" class="app-input"></div>
    <div><label class="app-label">Venue</label><input name="venue" value="{{ old('venue', $c->venue) }}" class="app-input"></div>
    <div class="sm:col-span-2"><label class="app-label">Breeder line</label><input name="breeder_line" value="{{ old('breeder_line', $c->breeder_line ?? auth()->user()->breederLine()) }}" placeholder="0696358  DIE BULT MEATMASTER STOET, POSBUS 42, KENHARDT, 8900" class="app-input font-mono text-xs"></div>
</div>
