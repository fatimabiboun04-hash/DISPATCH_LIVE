<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\User;
use App\Imports\PlanningImport;
use App\Services\WhatsAppService;
use App\Http\Requests\StorePlanningRequest;
use App\Http\Requests\UpdatePlanningRequest;
use App\Http\Resources\PlanningResource;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PlanningController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsApp
    ) {}

    // ==================
    // HELPER — Calcul heures
    // ==================
    private function calculHeures(
        string $debut,
        string $fin,
        int    $pause,
        string $date,
        string $shift,
        ?int   $excludeId = null
    ): array {
        $d = Carbon::parse($debut);
        $f = Carbon::parse($fin);

        if ($shift === 'N') $f->addDay();

        $totalMinutes = $f->diffInMinutes($d) - $pause;
        $heuresJour   = round($totalMinutes / 60, 2);

        $startWeek = Carbon::parse($date)->startOfWeek();
        $endWeek   = Carbon::parse($date)->endOfWeek();

        $query = Planning::whereBetween('date', [$startWeek, $endWeek]);
        if ($excludeId) $query->where('id', '!=', $excludeId);

        $heuresSemaine = $query->sum('heures_reelles');
        $over44        = ($heuresSemaine + $heuresJour) > 44;

        return [
            'heures_reelles' => $heuresJour,
            'over_44h'       => $over44,
        ];
    }

    // ==================
    // ADMIN
    // ==================
    public function index(Request $request)
    {
        $query = Planning::with(['user.equipe', 'taches']);

        if ($request->filled('user_id'))   $query->where('user_id', $request->user_id);
        if ($request->filled('date'))      $query->where('date', $request->date);
        if ($request->filled('shift'))     $query->where('shift', $request->shift);
        if ($request->filled('equipe_id')) {
            $query->whereHas('user', fn($q) =>
                $q->where('equipe_id', $request->equipe_id)
            );
        }

        $plannings = $query->paginate(10);

        return response()->json([
            'data'         => PlanningResource::collection($plannings->items()),
            'total'        => $plannings->total(),
            'per_page'     => $plannings->perPage(),
            'current_page' => $plannings->currentPage(),
            'last_page'    => $plannings->lastPage(),
        ]);
    }

    public function store(StorePlanningRequest $request)
    {
        $exists = Planning::where('user_id', $request->user_id)
                          ->where('date',    $request->date)
                          ->where('shift',   $request->shift)
                          ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Had employe 3ndu planning deja f-had date w shift'
            ], 422);
        }

        $calcul = $this->calculHeures(
            $request->heure_debut,
            $request->heure_fin,
            $request->pause_minutes,
            $request->date,
            $request->shift
        );

        $planning = Planning::create([
            ...$request->validated(),
            'heures_reelles' => $calcul['heures_reelles'],
            'over_44h'       => $calcul['over_44h'],
        ]);

        $planning->load(['user.equipe', 'taches']);

        $this->whatsApp->sendPlanningCreated(
            $planning->user->nom,
            $planning->date,
            $planning->shift,
            $calcul['over_44h']
        );

        return response()->json([
            'message'  => 'Planning créé avec succès',
            'over_44h' => $calcul['over_44h'],
            'planning' => new PlanningResource($planning),
        ], 201);
    }

    public function update(UpdatePlanningRequest $request, $id)
    {
        $planning = Planning::findOrFail($id);

        if ($request->filled('date') || $request->filled('shift')) {
            $exists = Planning::where('user_id', $planning->user_id)
                              ->where('date',  $request->date  ?? $planning->date)
                              ->where('shift', $request->shift ?? $planning->shift)
                              ->where('id', '!=', $id)
                              ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Planning kayna deja f-had date w shift'
                ], 422);
            }
        }

        $calcul = $this->calculHeures(
            $request->heure_debut   ?? $planning->heure_debut,
            $request->heure_fin     ?? $planning->heure_fin,
            $request->pause_minutes ?? $planning->pause_minutes,
            $request->date          ?? $planning->date,
            $request->shift         ?? $planning->shift,
            $id
        );

        $planning->update([
            ...$request->validated(),
            'heures_reelles' => $calcul['heures_reelles'],
            'over_44h'       => $calcul['over_44h'],
        ]);

        $planning->load(['user.equipe', 'taches']);

        $this->whatsApp->sendPlanningUpdated(
            $planning->user->nom,
            $planning->date,
            $planning->shift,
            $calcul['over_44h']
        );

        return response()->json([
            'message'  => 'Planning modifié avec succès',
            'over_44h' => $calcul['over_44h'],
            'planning' => new PlanningResource($planning),
        ]);
    }

    public function destroy($id)
    {
        Planning::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Planning supprimé avec succès'
        ]);
    }

    // ==================
    // EMPLOYE + ADMIN
    // ==================
    public function monPlanning(Request $request)
    {
        $user     = $request->user();
        $equipeId = $user->equipe_id;

        $query = Planning::with(['user.equipe', 'taches'])
                         ->whereHas('user', fn($q) =>
                             $q->where('equipe_id', $equipeId)
                         );

        if ($request->filled('date'))  $query->where('date', $request->date);
        if ($request->filled('shift')) $query->where('shift', $request->shift);

        $plannings = $query->paginate(10);

        return response()->json([
            'data'         => PlanningResource::collection($plannings->items()),
            'total'        => $plannings->total(),
            'per_page'     => $plannings->perPage(),
            'current_page' => $plannings->currentPage(),
            'last_page'    => $plannings->lastPage(),
        ]);
    }

    // ==================
    // TACHES
    // ==================
    public function addTache(Request $request, $id)
    {
        $planning = Planning::findOrFail($id);

        $request->validate([
            'tache_id' => 'required|exists:taches,id',
        ]);

        if ($planning->taches()->where('tache_id', $request->tache_id)->exists()) {
            return response()->json([
                'message' => 'Tache kayna deja f-had planning'
            ], 422);
        }

        $planning->taches()->attach($request->tache_id);

        return response()->json([
            'message' => 'Tache ajoutée au planning avec succès'
        ]);
    }

    public function removeTache($id, $tache_id)
    {
        $planning = Planning::findOrFail($id);

        if (!$planning->taches()->where('tache_id', $tache_id)->exists()) {
            return response()->json([
                'message' => 'Tache machi kayna f-had planning'
            ], 404);
        }

        $planning->taches()->detach($tache_id);

        return response()->json([
            'message' => 'Tache supprimée du planning avec succès'
        ]);
    }

    // ==================
    // IMPORT EXCEL
    // ==================
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        $import = new PlanningImport;
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message'  => "Import réussi — {$import->imported} plannings créés",
            'imported' => $import->imported,
            'warnings' => $import->warnings,
        ]);
    }

    // ==================
    // TEMPLATE EXCEL
    // ==================
    public function templateExcel()
    {
        $columns = [
            ['nom_equipe', 'date',       'shift', 'conges'],
            ['Equipe 1',  '2026-04-21', 'J',     ''],
            ['Equipe 2',  '2026-04-21', 'N',     'Mohammed ALAMI'],
            ['Cockpit HO','2026-04-21', 'HO',    ''],
        ];

        return Excel::download(
            new class($columns) implements \Maatwebsite\Excel\Concerns\FromArray {
                public function __construct(private array $data) {}
                public function array(): array { return $this->data; }
            },
            'template_planning.xlsx'
        );
    }
}
