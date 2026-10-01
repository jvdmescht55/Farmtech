@extends('layouts.rfid')
@section('title', 'Farm settings')
@section('content')
<form method="POST" action="{{ route('rfid.settings.update') }}" class="max-w-3xl space-y-6">
    @csrf @method('PUT')
    <div class="app-card p-6">
        <h2 class="font-headline text-3xl">Your farm</h2>
        <p class="text-sm text-stone mt-1 mb-5">Printed on your sale catalogues, and used to set things up the way you farm.</p>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="app-label">Farm / stud name</label><input name="farm_name" value="{{ old('farm_name', $user->farm_name) }}" placeholder="Die Bult Meatmaster Stoet" class="app-input"></div>
            <div><label class="app-label">Breeder number</label><input name="breeder_number" value="{{ old('breeder_number', $user->breeder_number) }}" placeholder="0696358" class="app-input font-mono"></div>
            <div class="sm:col-span-2"><label class="app-label">Postal address</label><input name="farm_address" value="{{ old('farm_address', $user->farm_address) }}" placeholder="Posbus 42, Kenhardt, 8900" class="app-input"></div>
            <div><label class="app-label">Stud prefix</label><input name="stud_prefix" value="{{ old('stud_prefix', $user->stud_prefix) }}" placeholder="DVS" class="app-input font-mono"></div>
            <div><label class="app-label">Mostly farming</label><select name="species" class="app-input">@foreach (config('herd.species') as $k => $sp)<option value="{{ $k }}" @selected(old('species', $user->species) === $k)>{{ $sp['plural'] }}</option>@endforeach</select></div>
            <div><label class="app-label">Main breed</label><input name="breed" value="{{ old('breed', $user->breed) }}" placeholder="Meatmaster" class="app-input"></div>
        </div>
        @if ($user->breederLine())<div class="mt-5 rounded-lg bg-char text-white px-4 py-2 font-mono text-xs">{{ $user->breederLine() }}</div>@endif
    </div>
    <div class="app-card p-6">
        <h2 class="font-headline text-3xl mb-5">Your account</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="app-label">Name</label><input name="name" value="{{ old('name', $user->name) }}" required class="app-input"></div>
            <div><label class="app-label">Cellphone</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="app-input"></div>
            <div class="sm:col-span-2"><label class="app-label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="app-input"></div>
            <div><label class="app-label">Current password</label><input type="password" name="current_password" autocomplete="current-password" class="app-input"></div>
            <div></div>
            <div><label class="app-label">New password</label><input type="password" name="password" autocomplete="new-password" class="app-input"></div>
            <div><label class="app-label">New password again</label><input type="password" name="password_confirmation" autocomplete="new-password" class="app-input"></div>
        </div>
    </div>
    <div class="app-card p-6">
        <h2 class="font-headline text-3xl mb-3">Your devices &amp; sections</h2>
        <ul class="text-sm space-y-2">
            @forelse ($user->licenses as $l)
                <li class="flex justify-between gap-3"><span>{{ $l->moduleLabel() }} <span class="font-mono text-xs text-stone-light">{{ $l->code }}</span></span><span class="text-xs {{ $l->revoked_at ? 'text-[#B0452F]' : 'text-[#3F7A3A]' }}">{{ $l->revoked_at ? 'Revoked' : 'Active since '.$l->activated_at?->format('d/m/Y') }}</span></li>
            @empty
                <li class="text-stone">{{ $user->isAdmin() ? 'Admin account — everything unlocked.' : 'No device activated yet.' }}</li>
            @endforelse
        </ul>
        <a href="{{ route('account.activate') }}" class="inline-block mt-4 text-sm font-medium text-char hover:underline">Activate another device →</a>
    </div>
    <button class="btn-primary">Save</button>
</form>
@endsection
