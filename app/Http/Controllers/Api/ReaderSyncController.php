<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reader;
use App\Services\Herd\ScanImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/reader/sync
 * Authorization: Bearer <reader token>
 * {"scans": [{"eid": "982000123456789", "visual_id": "DVS 25 5082", "weight": 42.5, "scanned_at": "2026-09-30 08:15"}]}
 */
class ReaderSyncController extends Controller
{
    public function store(Request $request, ScanImporter $importer): JsonResponse
    {
        $reader = Reader::findByToken($request->bearerToken());

        if (! $reader || ! $reader->user->hasModule('rfid')) {
            return response()->json(['error' => 'Invalid or unlicensed reader token.'], 401);
        }

        $data = $request->validate([
            'scans' => ['required', 'array', 'min:1', 'max:5000'],
            'scans.*' => ['array'],
        ]);

        $sync = $importer->import($reader->user, $reader, 'api', $data['scans']);

        return response()->json([
            'sync_id' => $sync->id,
            'scans' => $sync->scan_count,
            'matched' => $sync->matched_count,
            'new_animals' => $sync->new_count,
        ], 201);
    }
}
