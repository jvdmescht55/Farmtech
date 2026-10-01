<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Reader;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function landing(Request $request)
    {
        if ($request->user()) {
            return redirect($this->home($request->user()));
        }

        return view('public.landing');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Those credentials do not match our records.'])->onlyInput('email');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account has been deactivated. Contact Farmtech support.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->home(Auth::user()));
    }

    public function showRegister()
    {
        return view('public.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'farm_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $license = $this->availableLicense($data['code']);

        $user = DB::transaction(function () use ($data, $license) {
            $user = User::create([
                'name' => $data['name'],
                'farm_name' => $data['farm_name'] ?? null,
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'customer',
                'is_active' => true,
            ]);
            $this->redeem($license, $user);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('rfid.dashboard')->with('status', 'Welcome! Your '.$license->moduleLabel().' is unlocked.');
    }

    public function showActivate()
    {
        return view('public.activate');
    }

    public function activate(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $license = $this->availableLicense($data['code']);
        DB::transaction(fn () => $this->redeem($license, $request->user()));

        return redirect()->route('herd.hub')->with('status', $license->moduleLabel().' unlocked.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    private function availableLicense(string $code): License
    {
        $license = License::where('code', License::normalizeCode($code))->lockForUpdate()->first();

        if (! $license || ! $license->isAvailable()) {
            throw ValidationException::withMessages(['code' => 'That activation code is invalid or has already been used.']);
        }

        return $license;
    }

    /** Binds the licence to the account and registers the device it shipped with. */
    private function redeem(License $license, User $user): void
    {
        $license->update(['user_id' => $user->id, 'activated_at' => now()]);

        if ($license->module === 'rfid') {
            Reader::create([
                'user_id' => $user->id,
                'license_id' => $license->id,
                'name' => $license->device_model ?: 'RFID reader',
                'serial' => $license->device_serial,
                'model' => $license->device_model,
            ]);
        }
    }

    private function home(User $user): string
    {
        return $user->canAccessAdminPanel() ? url('/admin') : route('herd.hub');
    }
}
