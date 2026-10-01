<?php

namespace App\Http\Controllers\Rfid;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rfid\Concerns\OwnsRecords;
use App\Models\Animal;
use App\Models\AnimalEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    use OwnsRecords;

    public function index(Request $request)
    {
        $events = AnimalEvent::with('animal', 'mate')
            ->where('user_id', $request->user()->id)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(50)->withQueryString();

        return view('rfid.events', [
            'events' => $events,
            'animals' => Animal::where('user_id', $request->user()->id)->where('in_herd', true)->where('status', 'active')->orderBy('visual_id')->get(['id', 'visual_id', 'sex']),
            'males' => Animal::where('user_id', $request->user()->id)->where('sex', 'M')->orderBy('visual_id')->get(['id', 'visual_id']),
        ]);
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;
        $data = $request->validate([
            'animal_ids' => ['required', 'array', 'min:1'],
            'animal_ids.*' => ['integer', Rule::exists('animals', 'id')->where('user_id', $userId)],
            'type' => ['required', Rule::in(array_keys(config('herd.event_types')))],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'product' => ['nullable', 'string', 'max:255'],
            'dose' => ['nullable', 'string', 'max:64'],
            'withdrawal_days' => ['nullable', 'integer', 'min:0', 'max:400'],
            'mate_id' => ['nullable', 'integer', Rule::exists('animals', 'id')->where('user_id', $userId)],
            'count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'result' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $withdrawal = isset($data['withdrawal_days']) && $data['withdrawal_days'] !== null
            ? now()->parse($data['date'])->addDays((int) $data['withdrawal_days'])->toDateString() : null;

        foreach ($data['animal_ids'] as $id) {
            AnimalEvent::create([
                'user_id' => $userId, 'animal_id' => $id, 'type' => $data['type'], 'date' => $data['date'],
                'product' => $data['product'] ?? null, 'dose' => $data['dose'] ?? null, 'withdrawal_until' => $withdrawal,
                'mate_id' => $data['mate_id'] ?? null, 'count' => $data['count'] ?? null, 'result' => $data['result'] ?? null, 'notes' => $data['notes'] ?? null,
            ]);
        }

        return back()->with('status', config("herd.event_types.{$data['type']}.label").' recorded for '.count($data['animal_ids']).' '.(count($data['animal_ids']) === 1 ? 'animal' : 'animals').'.');
    }

    public function destroy(AnimalEvent $event)
    {
        $this->own($event);
        $event->delete();

        return back()->with('status', 'Entry removed.');
    }
}
