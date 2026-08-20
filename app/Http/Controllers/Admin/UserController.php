<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Creates the account and shows the generated temporary password once,
     * on this redirect — it's hashed in the DB and never stored or logged
     * anywhere in plain text, so this is the only chance to see it. Not an
     * emailed invite (no separate "set your password" flow exists yet —
     * see PROGRESS.md); the new admin should change it via their own
     * /admin/profile on first login.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,staff'],
        ]);

        $temporaryPassword = Str::password(16);

        User::create([
            ...$validated,
            'password' => Hash::make($temporaryPassword),
        ]);

        return redirect()->route('admin.users.index')->with([
            'status' => "Account created for {$validated['email']}.",
            'temporary_password' => $temporaryPassword,
        ]);
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => "You can't deactivate your own account."]);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->is_active ? "{$user->name} reactivated." : "{$user->name} deactivated.");
    }
}
