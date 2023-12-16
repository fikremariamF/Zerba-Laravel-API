<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Foam;
use App\Models\Sprint;

class FoamController extends Controller
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

        // Fetch Foams associated with the active sprint
        $activeSprint = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->first();

        $foams = Foam::where('sprint_id', $activeSprint->id)
            ->get(['id', 'date', 'sold', 'percentage', 'sprint_id']);

        return response()->json($foams);
    }

    public function update(Request $request, $id)
    {
        
        $request->validate([
            'sold' => 'required|numeric',
            'profit' => 'required|numeric'
        ]);

        $foam = Foam::findOrFail($id);


        // Check if the foam's sprint is active
        $sprintIsActive = Sprint::where('id', $foam->sprint_id)
            ->where('is_active', true)
            ->exists();
        
        if (!$sprintIsActive) {
            return response()->json(['error' => 'The foam is not associated with an active sprint.'], 400);
        }

        // Perform the calculations and update
        $foam->sold += $request->sold;
        $foam->percentage += $request->sold * ($request->profit / 100);

        $foam->save();

        return response()->json($foam);
    }
}
