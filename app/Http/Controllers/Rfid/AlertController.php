<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Models\AlertDismissal;
use App\Services\Herd\HerdAlerts;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request, HerdAlerts $engine)
    {
        $all = $engine->forUser($request->user()->id, includeDismissed: $request->boolean('dismissed'));

        $alerts = $all
            ->when($request->filled('severity'), fn ($c) => $c->where('severity', $request->input('severity')))
            ->when($request->filled('category'), fn ($c) => $c->where('category', $request->input('category')));

        return view('rfid.alerts', [
            'alerts' => $alerts,
            'counts' => $all->where('dismissed', false)->countBy('severity'),
            'byCategory' => $all->where('dismissed', false)->countBy('category'),
        ]);
    }

    public function dismiss(Request $request)
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:120'], 'days' => ['nullable', 'integer', 'min:1', 'max:365']]);
        AlertDismissal::updateOrCreate(
            ['user_id' => $request->user()->id, 'alert_key' => $data['key']],
            ['until' => isset($data['days']) ? now()->addDays((int) $data['days']) : null],
        );

        return back()->with('status', 'Alert hidden.');
    }

    public function restore(Request $request)
    {
        AlertDismissal::where('user_id', $request->user()->id)->where('alert_key', $request->input('key'))->delete();

        return back();
    }

    public function export(Request $request, HerdAlerts $engine)
    {
        $alerts = $engine->forUser($request->user()->id);

        return response()->streamDownload(function () use ($alerts) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Severity', 'Category', 'Animal', 'EID', 'Alert', 'Detail', 'Suggested action']);
            foreach ($alerts as $a) {
                fputcsv($out, [$a['severity'], HerdAlerts::CATEGORIES[$a['category']] ?? $a['category'], $a['animal']?->visual_id, $a['animal']?->eid, $a['title'], $a['detail'], $a['action']]);
            }
            fclose($out);
        }, 'waarskuwings-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
