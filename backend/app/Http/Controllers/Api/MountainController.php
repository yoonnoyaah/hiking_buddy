<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mountain;

class MountainController extends Controller
{
    // Get all active mountains
    public function index()
    {
        $mountains = Mountain::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'mountains' => $mountains
        ]);
    }

    // Get one mountain and its details
    public function show($id)
    {
        $mountain = Mountain::where('is_active', true)
            ->find($id);

        if (!$mountain) {
            return response()->json([
                'message' => 'Mountain not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'mountain' => $mountain
        ]);
    }
}