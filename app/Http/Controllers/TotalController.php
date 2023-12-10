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

    public function update(Request $resuest, $id)
    {
    }
}
