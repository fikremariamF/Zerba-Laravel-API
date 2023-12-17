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
use Illuminate\Support\Facades\DB;

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
        $sprint = Sprint::find($sprintId);
        if (!$sprint) {
            return response()->json(['error' => 'No sprint found for the user.'], 404);
        }

        $foams = Foam::where('sprint_id', $sprintId)->get();
        $cherks = Cherk::where('sprint_id', $sprintId)->get();
        $myCosts = MyCost::where('sprint_id', $sprintId)->get();
        $tsCosts = TsCost::where('sprint_id', $sprintId)->get();

        $totals = DB::table('totals')
            ->join('cherks', 'totals.cherk_id', '=', 'cherks.id')
            ->where('totals.sprint_id', $sprintId)
            ->select('totals.id', 'totals.date', 'totals.sold', 'cherks.sold as cherk', 'totals.bergamod', 'totals.sprint_id')
            ->get();

        $Bnet = $foams->sum(function ($cherk) {
            return $cherk->sold - $cherk->percentage;
        });

        $Snet = $totals->sum(function ($total) {
            return $total->sold - $total->cherk - $total->bergamod;
        });

        $tsCost = $tsCosts->sum('spent');

        $myCost = $myCosts->sum('spent');

        $myProfit = $foams->sum('percentage') + $cherks->sum('percentage');

        $initialDebt = $this->getInitialDebt($sprintId);

        $TotNet = $Bnet + $initialDebt - ($Snet + $tsCost);

        $startDate = $sprint->startDate;
        $endDate = Carbon::parse($startDate)->addDays(10);

        return response()->json([
            'foams' => $foams,
            'cherks' => $cherks,
            'totals' => $totals,
            'my_costs' => $myCosts,
            'ts_costs' => $tsCosts,
            'net' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Bnet' => $Bnet,
                'initialDebt' => $initialDebt,
                'Snet' => $Snet + $tsCost,
                'TotNet' => $TotNet
            ],
            'PersonalProfit' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Mycost' => $myCost,
                'MyProfit' => $myProfit,
                'NetProfit' => $myCost - $myProfit
            ]
        ]);
    }

    public function getInitialDebt($sprintId)
    {
        $initialDebt = 0;
        while ($sprintId) {
            $sprint = Sprint::where('id', '<', $sprintId)->where('is_active', false)->latest('id')->first();

            if (!$sprint)
                break;

            $initialDebt += $sprint->TotNet;

            $sprintId = $sprint->id;
        }

        return $initialDebt;
    }

    public function getPersonalExpenseData()
    {
        $user = auth()->user();

        $activeSprintExists = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->exists();

        if (!$activeSprintExists) {
            return response()->json(['error' => 'No active sprint found for the user.'], 404);
        }

        $activeSprint = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->first();

        if (!$activeSprint) {
            return response()->json(['error' => 'No active sprint found for the user.'], 404);
        }

        $foams = Foam::where('sprint_id', $activeSprint->id)->get();
        $cherks = Cherk::where('sprint_id', $activeSprint->id)->get();
        $myCosts = MyCost::where('sprint_id', $activeSprint->id)->get();

        $myProfit = $foams->sum('percentage') + $cherks->sum('percentage');
        $myCost = $myCosts->sum('spent');

        $startDate = $activeSprint->startDate;
        $endDate = Carbon::parse($startDate)->addDays(10);

        return response()->json([
            'PersonalProfit' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Mycost' => $myCost,
                'MyProfit' => $myProfit,
                'NetProfit' => $myCost - $myProfit
            ]
        ]);
    }

    public function getSprintExpenseData(){
        $user = auth()->user();

        $activeSprintExists = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->exists();

        if (!$activeSprintExists) {
            return response()->json(['error' => 'No active sprint found for the user.'], 404);
        }

        $activeSprint = Sprint::where('is_active', true)
            ->where('user_id', $user->id)
            ->first();

        if (!$activeSprint) {
            return response()->json(['error' => 'No active sprint found for the user.'], 404);
        }

        $foams = Foam::where('sprint_id', $activeSprint->id)->get();
        $tsCosts = TsCost::where('sprint_id', $activeSprint->id)->get();
        $totals = DB::table('totals')
            ->join('cherks', 'totals.cherk_id', '=', 'cherks.id')
            ->where('totals.sprint_id', $activeSprint->id)
            ->select('totals.id', 'totals.date', 'totals.sold', 'cherks.sold as cherk', 'totals.bergamod', 'totals.sprint_id')
            ->get();

        $Bnet = $foams->sum(function ($foam) {
            return $foam->sold - $foam->percentage;
        });

        $Snet = $totals->sum(function ($total) {
            return $total->sold - $total->cherk - $total->bergamod;
        });

        $initialDebt = $this->getInitialDebt($activeSprint->id);
        $tsCost = $tsCosts->sum('spent');
        $TotNet = $Bnet + $initialDebt - ($Snet + $tsCost);

        $startDate = $activeSprint->startDate;
        $endDate = Carbon::parse($startDate)->addDays(10);
        
        return response()->json([
            'net' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Bnet' => $Bnet,
                'initialDebt' => $initialDebt,
                'Snet' => $Snet + $tsCost,
                'TotNet' => $TotNet
            ],
        ]);
    }
}

