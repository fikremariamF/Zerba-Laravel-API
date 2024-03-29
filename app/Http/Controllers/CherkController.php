<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cherk;
use App\Models\Sprint;

class CherkController extends Controller
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

        $activeSprint = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->first();

        // Fetch Cherks associated with the active sprint
        $cherks = Cherk::where('sprint_id', $activeSprint->id)
            ->get(['id', 'date', 'sold', 'percentage', 'sprint_id']);

        return response()->json($cherks);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'sold' => 'required|numeric',
            'profit' => 'required|numeric'
        ]);

        $cherk = Cherk::findOrFail($id);


        // Check if the foam's sprint is active
        // $sprintIsActive = Sprint::where('id', $cherk->sprint_id)
        //     ->where('is_active', true)
        //     ->exists();

        // if (!$sprintIsActive) {
        //     return response()->json(['error' => 'The foam is not associated with an active sprint.'], 400);
        // }

        // Perform the calculations and update
        $cherk->sold += $request->sold;
        $cherk->percentage += $request->sold * ($request->profit / 100);

        $cherk->save();

        return response()->json($cherk);
    }
}
