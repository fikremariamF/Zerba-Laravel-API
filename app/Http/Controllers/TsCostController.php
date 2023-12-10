<?php

namespace App\Http\Controllers;

use App\Models\TsCost;
use App\Models\Sprint;
use Illuminate\Http\Request;

class TsCostController extends Controller
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

        // Fetch TsCosts associated with the active sprint
        $myCosts = TsCost::where('sprint_id', $activeSprint->id)
            ->get(['id', 'date', 'spent', 'sprint_id']);

        return response()->json($myCosts);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'cost' => 'required|numeric'
        ]);

        $tsCost = TsCost::findOrFail($id);

        $sprintIsActive = Sprint::where('id', $tsCost->sprint_id)
            ->where('is_active', true)
            ->exists();

        if (!$sprintIsActive) {
            return response()->json(['error' => 'The foam is not associated with an active sprint.'], 400);
        }

        $tsCost->spent += $request->cost;
        $tsCost->save();
        return response()->json($tsCost);
    }
}
