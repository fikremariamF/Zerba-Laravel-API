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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

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
        DB::beginTransaction();
        $sprint = Sprint::create([
            'startDate' => $date,
            'user_id' => $user->id
        ]);

        if (!$sprint->id) {
            return response()->json(['error' => 'Failed to create sprint.', 'sprint' => $sprint], 500);
        }

        $startDate = Carbon::parse($sprint->startDate);

        $bulkData = [];
        $totals = [];

        try {
            for ($i = 0; $i < 10; $i++) {
                $currentDate = $startDate->copy()->addDays($i);
                $bulkData[] = ['date' => $currentDate, 'sprint_id' => $sprint->id];
            }

            Foam::insert($bulkData);
            Cherk::insert($bulkData);
            MyCost::insert($bulkData);
            TsCost::insert($bulkData);

            $cherks = Cherk::where('sprint_id', $sprint->id)->orderBy('id')->get();

            foreach ($cherks as $index => $cherk) {
                $totals[] = [
                    'date' => $bulkData[$index]['date'],
                    'cherk_id' => $cherk->id,
                    'sprint_id' => $sprint->id
                ];
            }

            Total::insert($totals);
            DB::commit();
            return response()->json($sprint, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create sprint.', 'message' => $e->getMessage()], 500);
        }
    }

    public function getInactiveSprints()
    {
        $user = auth()->user();
        $inactiveSprints = Sprint::where('is_active', false)->where('user_id', $user->id)
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
        $user = auth()->user();
        $sprint = Sprint::where('user_id', $user->id)->where('id', $sprintId)
            ->first();
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

        $TotNet = $Bnet + $sprint->debt - ($Snet + $tsCost);

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
                'initialDebt' => $sprint->debt,
                'Snet' => $Snet + $tsCost,
                'TotNet' => $TotNet
            ],
            'PersonalProfit' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Mycost' => $myCost,
                'MyProfit' => $myProfit,
                'NetProfit' => $myProfit - $myCost
            ]
        ]);
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
        $endDate = Carbon::parse($startDate)->addDays(9);

        return response()->json([
            'PersonalProfit' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Mycost' => $myCost,
                'MyProfit' => $myProfit,
                'NetProfit' => $myProfit - $myCost
            ]
        ]);
    }

    public function getSprintExpenseData()
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

        $tsCost = $tsCosts->sum('spent');
        $TotNet = $Bnet + $activeSprint->debt - ($Snet + $tsCost);

        $startDate = $activeSprint->startDate;
        $endDate = Carbon::parse($startDate)->addDays(9);

        return response()->json([
            'net' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Bnet' => $Bnet,
                'initialDebt' => $activeSprint->debt,
                'Snet' => $Snet + $tsCost,
                'TotNet' => $TotNet
            ],
        ]);
    }

    public function generatePDF($sprintId)
    {
        $user = auth()->user();
        $sprint = Sprint::where('user_id', $user->id)->where('id', $sprintId)
            ->first();
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

        $TotNet = $Bnet + $sprint->debt - ($Snet + $tsCost);

        $startDate = $sprint->startDate;
        $endDate = Carbon::parse($startDate)->addDays(10);
        $data = [
            'foams' => $foams,
            'cherks' => $cherks,
            'totals' => $totals,
            'my_costs' => $myCosts,
            'ts_costs' => $tsCosts,
            'net' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Bnet' => $Bnet,
                'initialDebt' => $sprint->debt,
                'Snet' => $Snet + $tsCost,
                'TotNet' => $TotNet
            ],
            'PersonalProfit' => [
                'startDate' => substr($startDate, 0, 10),
                'endDate' => substr($endDate, 0, 10),
                'Mycost' => $myCost,
                'MyProfit' => $myProfit,
                'NetProfit' => $myProfit - $myCost
            ]
        ];

        $nets = [
            'foams' => [
                'sold' => $foams->sum('sold'),
                'percentage' => $foams->sum('percentage'),
                'net' => $foams->sum(function ($foam) {
                    return $foam->sold - $foam->percentage;
                }),
            ],
            'cherks' => [
                'sold' => $cherks->sum('sold'),
                'percentage' => $cherks->sum('percentage'),
                'net' => $cherks->sum(function ($foam) {
                    return $foam->sold - $foam->percentage;
                }),
            ],
            'total' => [
                'sold' => $totals->sum('sold'),
                'cherk' => $totals->sum('cherk'),
                'bergamod' => $totals->sum('bergamod'),
                'net' => $totals->sum(function ($foam) {
                    return $foam->sold - $foam->cherk - $foam->bergamod;
                }),
            ],
            'my-costs' => [
                'spent' => $myCosts->sum('spent'),
            ],
            'ts-costs' => [
                'spent' => $tsCosts->sum('spent'),
            ],
        ];

        // Add these totals to the $data array
        $data['netals'] = $nets;
        Log::info('Data array', ['data' => $data]);
        $pdf = Pdf::loadView('pdf.table', ['data' => $data]);
        return $pdf->download('report.pdf');
    }

    public function updateDebt($id, Request $request)
    {
        $request->validate([
            'debt' => 'required|numeric',
        ]);
        $sprint = Sprint::find($id);

        if (!$sprint) {
            return response()->json(['error' => 'Sprint not found.'], 404);
        }

        $sprint->debt += $request->debt;
        $sprint->save();

        return response()->json($sprint, 200);
    }
}

