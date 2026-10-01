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
            return back()->withErrors(['email' => 'That email and password don\'t match. Try again, or reset your password.'])->onlyInput('email');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account is switched off. Give us a shout on the contact page.'])->onlyInput('email');
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
            'terms' => ['accepted'],
        ], ['terms.accepted' => 'Please accept the terms of use and privacy policy to continue.']);

        $license = $this->availableLicense($data['code']);

        $user = DB::transaction(function () use ($data, $license) {
            $user = User::create([
                'name' => $data['name'],
                'farm_name' => $data['farm_name'] ?? null,
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'customer',
                'is_active' => true,
                'terms_accepted_at' => now(),
            ]);
            $this->redeem($license, $user);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('herd.hub')->with('status', 'Welcome aboard! '.$license->moduleLabel().' is unlocked — let\'s get your herd in.');
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

        return redirect()->route('herd.hub')->with('status', $license->moduleLabel().' is unlocked. Lekker!');
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
            throw ValidationException::withMessages(['code' => 'That code isn\'t valid or has already been used. Check the card in the box, or ask us.']);
        }

        return $license;
    }

    /** Binds the licence to the account and registers the device it shipped with. */
    private function redeem(License $license, User $user): void
    {
        $license->update(['user_id' => $user->id, 'activated_at' => now()]);

        if (in_array($license->module, ['rfid', 'watch'], true)) {
            Reader::create([
                'user_id' => $user->id,
                'license_id' => $license->id,
                'kind' => $license->module === 'watch' ? 'watch' : 'handheld',
                'name' => $license->device_model ?: config("herd.modules.{$license->module}.name"),
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
