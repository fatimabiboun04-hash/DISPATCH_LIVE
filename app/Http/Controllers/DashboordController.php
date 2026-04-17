<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Equipe;
use App\Models\Tache;
use App\Models\Planning;
use App\Models\Repo;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today      = Carbon::today();
        $startWeek  = Carbon::now()->startOfWeek();
        $endWeek    = Carbon::now()->endOfWeek();

        // ==================
        // Statistiques générales (Optimized with selectRaw)
        // ==================
        $userStats = User::where('role', 'NoAdmin')
            ->selectRaw('
                COUNT(*) as total_employes,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as total_actifs,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as total_inactifs
            ')
            ->first();

        $stats = [
            'total_employes' => (int) $userStats->total_employes,
            'total_actifs'   => (int) $userStats->total_actifs,
            'total_inactifs' => (int) $userStats->total_inactifs,
            'total_equipes'  => Equipe::count(),
            'total_taches'   => Tache::count(),
        ];

        // ==================
        // Plannings
        // ==================
        $plannings = [
            'aujourd_hui'   => Planning::whereDate('date', $today)->count(),
            'cette_semaine' => Planning::whereBetween('date', [$startWeek, $endWeek])->count(),
        ];

        // ==================
        // Repos
        // ==================
        $repos = [
            'en_attente' => Repo::where('statut', 'en_attente')->count(),
            'accepte'    => Repo::where('statut', 'accepte')->count(),
            'refuse'     => Repo::where('statut', 'refuse')->count(),
        ];

        // ==================
        // Top 5 employes par rating
        // ==================
        $topEmployes = User::where('role', 'NoAdmin')
            ->orderBy('rating', 'desc')
            ->take(5)
            ->with('equipe')
            ->get()
            ->map(fn($u) => [
                'id'          => $u->id,
                'nom'         => $u->nom,
                'rating'      => $u->rating,
                'equipe'      => $u->equipe?->nom,
                'description' => $u->description,
                'is_active'   => $u->is_active,
            ]);

        // ==================
        // Equipes actives aujourd'hui
        // ==================
        $equipesAujourdhui = Equipe::whereHas('users.plannings', function ($q) use ($today) {
            $q->whereDate('date', $today);
        })
        ->withCount(['users as employes_aujourd_hui' => function ($q) use ($today) {
            $q->whereHas('plannings', fn($p) => $p->whereDate('date', $today));
        }])
        ->get()
        ->map(fn($e) => [
            'id'                   => $e->id,
            'nom'                  => $e->nom,
            'type'                 => $e->type,
            'employes_aujourd_hui' => $e->employes_aujourd_hui,
        ]);

        // ==================
        // Répartition shifts aujourd'hui
        // ==================
        $shifts = Planning::whereDate('date', $today)
            ->selectRaw('shift, count(*) as total')
            ->groupBy('shift')
            ->get()
            ->map(fn($s) => [
                'shift' => $s->shift,
                'total' => $s->total,
            ]);

        return response()->json([
            'stats'               => $stats,
            'plannings'           => $plannings,
            'repos'               => $repos,
            'top_employes'        => $topEmployes,
            'equipes_aujourd_hui' => $equipesAujourdhui,
            'shifts_aujourd_hui'  => $shifts,
        ]);
    }

    // ==================
    // RAPPORT HEBDOMADAIRE (Hada khallito kima kan)
    // ==================
    public function rapportHebdomadaire(Request $request)
    {
        $startWeek = $request->filled('debut')
            ? Carbon::parse($request->debut)->startOfDay()
            : Carbon::now()->startOfWeek();

        $endWeek = $request->filled('fin')
            ? Carbon::parse($request->fin)->endOfDay()
            : Carbon::now()->endOfWeek();

        $plannings = Planning::with(['user.equipe', 'taches'])
            ->whereBetween('date', [$startWeek, $endWeek])
            ->get();

        $heuresParEmploye = $plannings->groupBy('user_id')->map(function ($items) {
            $user         = $items->first()->user;
            $totalMinutes = $items->sum(function ($p) {
                $debut  = Carbon::parse($p->heure_debut);
                $fin    = Carbon::parse($p->heure_fin);
                return $fin->diffInMinutes($debut) - $p->pause_minutes;
            });

            return [
                'user_id'       => $user->id,
                'nom'           => $user->nom,
                'equipe'        => $user->equipe?->nom,
                'total_heures'  => round($totalMinutes / 60, 2),
                'nb_plannings'  => $items->count(),
            ];
        })->values();

        $repartitionShifts = $plannings
            ->groupBy('shift')
            ->map(fn($items, $shift) => [
                'shift' => $shift,
                'total' => $items->count(),
            ])->values();

        $couvertureEquipes = Equipe::withCount('users')
            ->get()
            ->map(function ($equipe) use ($plannings) {
                $employes     = $equipe->users->pluck('id');
                $nbPlanifies  = $plannings->whereIn('user_id', $employes)
                                           ->pluck('user_id')
                                           ->unique()
                                           ->count();
                $total        = $equipe->users_count;
                $taux         = $total > 0
                    ? round(($nbPlanifies / $total) * 100, 1)
                    : 0;

                return [
                    'equipe'       => $equipe->nom,
                    'type'         => $equipe->type,
                    'total'        => $total,
                    'nb_planifies' => $nbPlanifies,
                    'taux'         => $taux . '%',
                ];
            });

        return response()->json([
            'periode' => [
                'debut' => $startWeek->format('Y-m-d'),
                'fin'   => $endWeek->format('Y-m-d'),
            ],
            'total_plannings'     => $plannings->count(),
            'heures_par_employe'  => $heuresParEmploye,
            'repartition_shifts'  => $repartitionShifts,
            'couverture_equipes'  => $couvertureEquipes,
        ]);
    }
}
