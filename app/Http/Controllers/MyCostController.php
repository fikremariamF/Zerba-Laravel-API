<?php

namespace App\Http\Controllers;

use App\Models\MyCost;
use App\Models\Sprint;
use Illuminate\Http\Request;

class MyCostController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Check if there's an active sprint for the user
        $activeSprintExists = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->exists();

        if (!$activeSprintExists) {
            // Return an error response if no active sprint is found
            return response()->json(['error' => 'No active sprint found for the user.'], 404);
        }

        // Check if there's an active sprint for the user
        $activeSprint = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->first();

        if (!$activeSprint) {
            // Return an error response if no active sprint is found
            return response()->json(['error' => 'No active sprint found for the user.'], 404);
        }

        // Fetch MyCosts associated with the active sprint
        $myCosts = MyCost::where('sprint_id', $activeSprint->id)
            ->get(['id', 'date', 'spent', 'sprint_id']);

        return response()->json($myCosts);
    }

    public function update(Request $resuest, $id){}

}
