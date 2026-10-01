<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Guides you can come back to, plus first-run tour bookkeeping. */
class HelpController extends Controller
{
    public const GUIDES = [
        'getting-started' => ['Your first 10 minutes', 'From unboxing to your first weigh day.', 'start'],
        'excel' => ['Bring your records in from Excel', 'Herd book, weights or treatments — straight from a spreadsheet.', 'table'],
        'esp32' => ['Connect your scale (ESP32)', 'Wi-Fi, pairing, and what to do when there\'s no signal.', 'chip'],
        'pair' => ['Pair a KraalTrac', 'Six digits and you\'re connected.', 'link'],
        'weighing' => ['A weigh day, start to finish', 'Scan, weigh, and read the results.', 'scale'],
        'auction' => ['Make an auction book', 'Pick, number, print — Logix layout.', 'book'],
        'ids' => ['Birthday numbers (YYMMNN)', 'How 250912 tells you when a lamb was born.', 'tag'],
        'alerts' => ['What the alerts mean', 'And what to do about each one.', 'bell'],
        'watch' => ['Set up KraalTrac Watch', 'Count who comes to drink.', 'drop'],
        'custom' => ['Connect your own device', 'Tank gauges, rain meters, anything.', 'plug'],
    ];

    public function index()
    {
        return view('help.index');
    }

    public function show(string $guide)
    {
        abort_unless(array_key_exists($guide, self::GUIDES), 404);

        return view('help.'.$guide, ['guide' => $guide, 'meta' => self::GUIDES[$guide]]);
    }

    public function tourDone(Request $request, string $key)
    {
        $user = $request->user();
        $user->update(['tours_seen' => array_values(array_unique(array_merge($user->tours_seen ?? [], [$key])))]);

        return response()->json(['ok' => true]);
    }
}
