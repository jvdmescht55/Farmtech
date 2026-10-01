<?php

namespace App\Http\Controllers\Watch;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rfid\Concerns\OwnsRecords;
use App\Models\Reader;
use App\Services\Herd\WatchStats;
use Illuminate\Http\Request;

class WatchController extends Controller
{
    use OwnsRecords;

    public function dashboard(Request $request, WatchStats $stats)
    {
        $userId = $request->user()->id;
        $animals = $stats->animals($userId);
        $today = $animals->filter(fn ($a) => $a->last_at->isToday());

        return view('watch.dashboard', [
            'points' => $stats->pointSummary($userId),
            'expected' => $animals->count(),
            'seenToday' => $today->count(),
            'missed' => $animals->where('missed', true)->values(),
            'headcount' => $stats->headcount($userId),
            'hourly' => $stats->hourly($userId),
            'recent' => $animals->sortByDesc(fn ($a) => $a->last_at->timestamp)->take(8),
        ]);
    }

    public function animals(Request $request, WatchStats $stats)
    {
        $animals = $stats->animals($request->user()->id);
        if ($request->input('show') === 'missed') {
            $animals = $animals->where('missed', true);
        }

        return view('watch.animals', ['animals' => $animals->values()]);
    }

    public function points(Request $request, WatchStats $stats)
    {
        return view('watch.points', ['points' => $stats->pointSummary($request->user()->id), 'shownToken' => session('shownToken')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'alert_hours' => ['nullable', 'integer', 'min:2', 'max:240'],
        ]);
        $reader = $request->user()->readers()->create($data + ['kind' => 'watch', 'model' => 'KraalTrac Watch']);

        return back()->with('status', "Water point \"{$reader->name}\" added — copy the device key below.")->with('shownToken', [$reader->id => $reader->plainToken]);
    }

    public function update(Request $request, Reader $reader)
    {
        $this->own($reader);
        $reader->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'alert_hours' => ['nullable', 'integer', 'min:2', 'max:240'],
        ]));

        return back()->with('status', 'Saved.');
    }
}
