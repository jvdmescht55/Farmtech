<?php

namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rfid\Concerns\OwnsRecords;
use App\Models\DeviceReading;
use App\Models\Reader;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Farmer-defined devices: they name it, say what it measures, and set their own limits. */
class CustomDeviceController extends Controller
{
    use OwnsRecords;

    public function index(Request $request)
    {
        $devices = $request->user()->readers()->where('kind', 'custom')->orderBy('name')->get()->map(function (Reader $d) {
            $d->latest = DeviceReading::where('reader_id', $d->id)->orderByDesc('recorded_at')->get()->unique('metric')->keyBy('metric');

            return $d;
        });

        return view('custom.index', ['devices' => $devices, 'shownToken' => session('shownToken')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'metrics' => ['required', 'array', 'min:1', 'max:8'],
            'metrics.*.label' => ['nullable', 'string', 'max:40'],
            'metrics.*.unit' => ['nullable', 'string', 'max:12'],
            'metrics.*.min' => ['nullable', 'numeric'],
            'metrics.*.max' => ['nullable', 'numeric'],
        ]);
        $device = $request->user()->readers()->create([
            'kind' => 'custom', 'name' => $data['name'], 'location' => $data['location'] ?? null,
            'model' => 'Custom', 'metrics' => $this->metrics($data['metrics']),
        ]);

        return redirect()->route('custom.show', $device)->with('status', 'Device added. Copy its key now — you\'ll only see it once.')->with('shownToken', [$device->id => $device->plainToken]);
    }

    public function show(Request $request, Reader $device)
    {
        $this->own($device);
        abort_unless($device->kind === 'custom', 404);
        $days = in_array((int) $request->input('days'), [1, 7, 30, 90], true) ? (int) $request->input('days') : 7;

        $readings = DeviceReading::where('reader_id', $device->id)->where('recorded_at', '>=', now()->subDays($days))->orderBy('recorded_at')->get()->groupBy('metric');
        $charts = collect($device->metrics ?? [])->map(function ($m) use ($readings) {
            $points = $readings->get($m['key'], collect());
            // Keep charts light: at most ~120 points, averaged per bucket.
            $bucket = max(1, (int) ceil($points->count() / 120));
            $series = $points->chunk($bucket)->map(fn ($c) => ['label' => $c->last()->recorded_at->format('Y-m-d H:i'), 'value' => round($c->avg('value'), 2)])->values()->all();
            $last = $points->last();

            return $m + ['series' => $series, 'last' => $last, 'out' => $last && ((isset($m['min']) && $m['min'] !== null && $last->value < $m['min']) || (isset($m['max']) && $m['max'] !== null && $last->value > $m['max']))];
        });

        return view('custom.show', ['device' => $device, 'charts' => $charts, 'days' => $days, 'shownToken' => session('shownToken')]);
    }

    public function update(Request $request, Reader $device)
    {
        $this->own($device);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'metrics' => ['required', 'array', 'min:1', 'max:8'],
            'metrics.*.key' => ['nullable', 'string', 'max:40'],
            'metrics.*.label' => ['nullable', 'string', 'max:40'],
            'metrics.*.unit' => ['nullable', 'string', 'max:12'],
            'metrics.*.min' => ['nullable', 'numeric'],
            'metrics.*.max' => ['nullable', 'numeric'],
        ]);
        $device->update(['name' => $data['name'], 'location' => $data['location'] ?? null, 'metrics' => $this->metrics($data['metrics'])]);

        return back()->with('status', 'Saved.');
    }

    public function token(Reader $device)
    {
        $this->own($device);

        return back()->with('status', 'New key issued — the old one stops working now.')->with('shownToken', [$device->id => $device->rotateToken()]);
    }

    public function destroy(Reader $device)
    {
        $this->own($device);
        $device->delete();

        return redirect()->route('custom.index')->with('status', 'Device removed.');
    }

    private function metrics(array $rows): array
    {
        return collect($rows)->filter(fn ($m) => filled($m['label'] ?? null))->map(fn ($m) => [
            'key' => Str::slug($m['key'] ?? $m['label'], '_') ?: Str::slug($m['label'], '_'),
            'label' => $m['label'],
            'unit' => $m['unit'] ?? null,
            'min' => isset($m['min']) && $m['min'] !== '' ? (float) $m['min'] : null,
            'max' => isset($m['max']) && $m['max'] !== '' ? (float) $m['max'] : null,
        ])->unique('key')->values()->all();
    }
}
