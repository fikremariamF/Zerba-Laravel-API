<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Sprint;
use App\Models\Foam;
use App\Models\Cherk;
use App\Models\Total;
use App\Models\MyCost;
use App\Models\TsCost;
use Carbon\Carbon;

class SprintController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * Store a newly created sprint in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (Sprint::where('is_active', true)->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'An active sprint already exists.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'startDate' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $date = Carbon::parse($request->input('startDate'))->format('Y-m-d');

        $sprint = Sprint::create([
            'startDate' => $date,
            'user_id' => $user->id
        ]);

        if (!$sprint->id) {
            return response()->json(['error' => 'Failed to create sprint.', 'sprint' => $sprint], 500);
        }

        $startDate = Carbon::parse($sprint->startDate);

        for ($i = 0; $i < 10; $i++) {
            $currentDate = $startDate->copy()->addDays($i);

            // Create Foam
            Foam::create([
                'date' => $currentDate,
                'sprint_id' => $sprint->id,
            ]);

            // Create Cherk
            $cherk = Cherk::create([
                'date' => $currentDate,
                'sprint_id' => $sprint->id,
            ]);

            // Create Total
            Total::create([
                'date' => $currentDate,
                'cherk_id' => $cherk->id,
                'sprint_id' => $sprint->id,
            ]);

            // Create MyCost
            MyCost::create([
                'date' => $currentDate,
                'sprint_id' => $sprint->id,
            ]);

            // Create TsCost
            TsCost::create([
                'date' => $currentDate,
                'sprint_id' => $sprint->id,
            ]);
        }

        return response()->json($sprint, 201);
    }

    public function getInactiveSprints()
    {
        $inactiveSprints = Sprint::where('is_active', false)
            ->get()
            ->makeHidden(['created_at', 'updated_at']) // Optionally hide timestamps
            ->toArray();

        foreach ($inactiveSprints as $key => $sprint) {
            $inactiveSprints[$key]['startDate'] = substr($sprint['startDate'], 0, 10);
        }

        return response()->json($inactiveSprints);
    }



    /**
     * Deactivate the specified sprint.
     */
    public function deactivate($id)
    {
        $sprint = Sprint::find($id);

        if (!$sprint) {
            return response()->json(['error' => 'Sprint not found.'], 404);
        }

        $sprint->is_active = false;
        $sprint->save();

        return response()->json($sprint, 200);
    }

    /**
     * Remove the specified sprint from storage.
     */
    public function destroy($id)
    {
        $sprint = Sprint::find($id);

        if (!$sprint) {
            return response()->json(['error' => 'Sprint not found.'], 404);
        }

        $sprint->delete();
        return response()->json(null, 204);
    }

    public function getSprintData($sprintId)
    {
        $foams = Foam::where('sprint_id', $sprintId)->get();
        $cherks = Cherk::where('sprint_id', $sprintId)->get();
        $totals = Total::where('sprint_id', $sprintId)->get();
        $myCosts = MyCost::where('sprint_id', $sprintId)->get();
        $tsCosts = TsCost::where('sprint_id', $sprintId)->get();

        return response()->json([
            'foams' => $foams,
            'cherks' => $cherks,
            'totals' => $totals,
            'my_costs' => $myCosts,
            'ts_costs' => $tsCosts
        ]);
    }
}

