@extends('layouts.admin')

@section('heading', 'Staff & Admin Users')

@section('content')
    @if (session('temporary_password'))
        <div class="mb-6 bg-yellow-50 border border-yellow-300 rounded-xl p-5">
            <p class="font-semibold text-yellow-900 mb-1">Temporary password (shown once — copy it now)</p>
            <code class="block bg-white border border-yellow-300 rounded px-3 py-2 font-mono text-sm select-all">{{ session('temporary_password') }}</code>
            <p class="text-xs text-yellow-800 mt-2">This isn't emailed and isn't stored anywhere in plain text — if you navigate away without copying it, the only way to recover is resetting it directly in the database. Have the new admin change it from their own Profile page on first login.</p>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border rounded-xl overflow-hidden h-fit">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-500 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $user->name }} @if($user->id === auth()->id()) <span class="text-xs text-gray-400">(you)</span> @endif</td>
                            <td class="px-4 py-3 text-gray-500">{{ $user->email }}</td>
                            <td class="px-4 py-3 capitalize">{{ $user->role }}</td>
                            <td class="px-4 py-3">
                                <x-badge :color="$user->is_active ? 'green' : 'red'">{{ $user->is_active ? 'Active' : 'Deactivated' }}</x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @unless ($user->id === auth()->id())
                                    <form action="{{ route('admin.users.toggle-active', $user) }}" method="POST" onsubmit="return confirm('{{ $user->is_active ? 'Deactivate' : 'Reactivate' }} {{ $user->name }}?');">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium {{ $user->is_active ? 'text-red-600' : 'text-farmtech-green' }}">
                                            {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white border rounded-xl p-6 h-fit">
            <h2 class="font-semibold mb-4">Create Account</h2>
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full border rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded-md px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Role</label>
                    <select name="role" class="w-full border rounded-md px-3 py-2 text-sm">
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Only Admin can log in to /admin right now — Staff accounts are recorded but can't sign in yet.</p>
                </div>
                <button type="submit" class="w-full bg-farmtech-green text-white font-semibold px-4 py-2 rounded-md hover:bg-farmtech-green-dark">Create</button>
            </form>
        </div>
    </div>
@endsection
