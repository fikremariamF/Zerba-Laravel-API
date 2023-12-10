<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Total;
use App\Models\Sprint;

class TotalController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Check if there's an active sprint for the user
        $activeSprint = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->first();

        if (!$activeSprint) {
            // Return an error response if no active sprint is found
            return response()->json(['error' => 'No active sprint found for the user.'], 404);
        }

        // Fetch Totals with the 'sold' value from Cherks associated with the active sprint
        $totals = DB::table('totals')
            ->join('cherks', 'totals.cherk_id', '=', 'cherks.id')
            ->where('totals.sprint_id', $activeSprint->id)
            ->select('totals.id', 'totals.date', 'cherks.sold as cherk_sold', 'totals.bergamod', 'totals.sprint_id')
            ->get();

        return response()->json($totals);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'sold' => 'required|numeric',
            'bergamod' => 'required|numeric'
        ]);

        $total = Total::findOrFail($id);


        // Check if the foam's sprint is active
        $sprintIsActive = Sprint::where('id', $total->sprint_id)
            ->where('is_active', true)
            ->exists();

        if (!$sprintIsActive) {
            return response()->json(['error' => 'The foam is not associated with an active sprint.'], 400);
        }

        $total->sold += $request->sold;
        $total->bergamod += $request->bergamod;

        return response()->json($total);
    }
}
