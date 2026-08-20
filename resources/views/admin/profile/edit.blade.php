@extends('layouts.admin')

@section('heading', 'My Profile')

@section('content')
    <div class="max-w-lg space-y-6">
        <form action="{{ route('admin.profile.update') }}" method="POST" class="bg-white border rounded-xl p-6 space-y-4">
            @csrf
            @method('PUT')
            <h2 class="font-semibold">Profile</h2>
            <div>
                <label class="block text-sm font-medium mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border rounded-md px-3 py-2">
            </div>
            <button type="submit" class="bg-farmtech-green text-white font-semibold px-5 py-2 rounded-md hover:bg-farmtech-green-dark">Save</button>
        </form>

        <form action="{{ route('admin.profile.password') }}" method="POST" class="bg-white border rounded-xl p-6 space-y-4">
            @csrf
            @method('PUT')
            <h2 class="font-semibold">Change Password</h2>
            <div>
                <label class="block text-sm font-medium mb-1">Current Password</label>
                <input type="password" name="current_password" required class="w-full border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">New Password</label>
                <input type="password" name="password" required class="w-full border rounded-md px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required class="w-full border rounded-md px-3 py-2">
            </div>
            <button type="submit" class="bg-gray-800 text-white font-semibold px-5 py-2 rounded-md hover:bg-gray-900">Change Password</button>
        </form>
    </div>
@endsection
