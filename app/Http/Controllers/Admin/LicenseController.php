<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index(Request $request)
    {
        $licenses = License::with('user')
            ->when($request->input('filter') === 'unused', fn ($q) => $q->whereNull('user_id')->whereNull('revoked_at'))
            ->when($request->input('filter') === 'active', fn ($q) => $q->whereNotNull('user_id')->whereNull('revoked_at'))
            ->when($request->input('filter') === 'revoked', fn ($q) => $q->whereNotNull('revoked_at'))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $customers = User::where('role', 'customer')
            ->withCount(['animals' => fn ($q) => $q->where('in_herd', true), 'readers', 'licenses'])
            ->latest()
            ->get();

        return view('admin.licenses.index', [
            'licenses' => $licenses,
            'customers' => $customers,
            'newCodes' => session('newCodes', []),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:200'],
            'module' => ['required', 'in:'.implode(',', array_keys(License::MODULES))],
            'device_model' => ['nullable', 'string', 'max:64'],
            'device_serials' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $serials = collect(preg_split('/[\s,]+/', (string) ($data['device_serials'] ?? '')))->filter()->values();
        $quantity = $serials->isNotEmpty() ? $serials->count() : (int) $data['quantity'];

        $codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $codes[] = License::create([
                'code' => License::generateCode(),
                'module' => $data['module'],
                'device_model' => $data['device_model'] ?? null,
                'device_serial' => $serials[$i] ?? null,
                'notes' => $data['notes'] ?? null,
            ])->code;
        }

        return redirect()->route('admin.licenses.index')
            ->with('status', count($codes).' activation code(s) created.')
            ->with('newCodes', $codes);
    }

    public function revoke(License $license)
    {
        $license->update(['revoked_at' => now()]);

        return back()->with('status', "Code {$license->code} revoked.");
    }

    public function restore(License $license)
    {
        $license->update(['revoked_at' => null]);

        return back()->with('status', "Code {$license->code} restored.");
    }
}
