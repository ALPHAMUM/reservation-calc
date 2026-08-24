<?php

namespace App\Http\Controllers;

use App\Services\ScTargetService;
use Illuminate\Http\Request;

class ScTargetController extends Controller
{
    public function store(Request $request, ScTargetService $scTargetService)
    {
        $property = $request->input('property', 'island');
        if (!in_array($property, ['island', 'pines', 'city'], true)) {
            return response()->json(['error' => 'Invalid property'], 422);
        }

        $resNo = trim((string) $request->input('res_no', ''));
        $occupantIdx = (int) $request->input('occupant_idx', -1);
        $mode = strtolower(trim((string) $request->input('mode', '')));

        if ($resNo === '' || $occupantIdx < 0 || !in_array($mode, ScTargetService::MODES, true)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        $scTargetService->save($property, $resNo, $occupantIdx, $mode);

        return response()->json([
            'ok'      => true,
            'key'     => $resNo . ':' . $occupantIdx,
            'mode'    => $mode,
        ]);
    }
}
