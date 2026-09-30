<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = Lead::query()
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->input('status') === 'open', fn ($q) => $q->whereNull('handled_at'))
            ->latest()->paginate(50)->withQueryString();

        return view('admin.leads.index', [
            'leads' => $leads,
            'counts' => [
                'open' => Lead::whereNull('handled_at')->count(),
                'interest' => Lead::where('type', 'interest')->count(),
                'contact' => Lead::where('type', 'contact')->count(),
            ],
            'byProvince' => Lead::where('type', 'interest')->whereNotNull('province')->groupBy('province')->selectRaw('province, count(*) c')->orderByDesc('c')->pluck('c', 'province'),
            'byHerd' => Lead::where('type', 'interest')->whereNotNull('herd_size')->groupBy('herd_size')->selectRaw('herd_size, count(*) c')->pluck('c', 'herd_size'),
        ]);
    }

    public function toggle(Lead $lead)
    {
        $lead->update(['handled_at' => $lead->handled_at ? null : now()]);

        return back();
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Type', 'Name', 'Email', 'Phone', 'Farm', 'Herd size', 'Province', 'Message', 'Handled']);
            foreach (Lead::latest()->cursor() as $l) {
                fputcsv($out, [$l->created_at, $l->type, $l->name, $l->email, $l->phone, $l->farm_name, $l->herd_size, $l->province, $l->message, $l->handled_at]);
            }
            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
