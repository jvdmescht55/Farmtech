<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('rfid.settings', ['user' => $request->user()->load('licenses')]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:32'],
            'farm_name' => ['nullable', 'string', 'max:255'],
            'breeder_number' => ['nullable', 'string', 'max:32'],
            'stud_prefix' => ['nullable', 'string', 'max:16'],
            'farm_address' => ['nullable', 'string', 'max:255'],
            'breed' => ['nullable', 'string', 'max:64'],
            'species' => ['required', 'in:'.implode(',', array_keys(config('herd.species')))],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        unset($data['current_password']);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('status', 'Settings saved.');
    }
}
