<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlanningController extends Controller
{
    // ==================
    // HELPER — WhatsApp
    // ==================

    private function sendWhatsApp(string $message): void
    {
        $instance = env('ULTRAMSG_INSTANCE_ID');
        $token    = env('ULTRAMSG_TOKEN');
        $group    = env('WHATSAPP_GROUP_ID');

        Http::post("https://api.ultramsg.com/{$instance}/messages/chat", [
            'token' => $token,
            'to'    => $group,
            'body'  => $message,
        ]);
    }

    // ==================
    // ADMIN
    // ==================

    public function index(Request $request)
    {
        $query = Planning::with(['user.equipe', 'taches']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        if ($request->filled('equipe_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('equipe_id', $request->equipe_id);
            });
        }

        $plannings = $query->get()->map(function ($planning) {
            return [
                'id'             => $planning->id,
                'date'           => $planning->date,
                'shift'          => $planning->shift,
                'heure_debut'    => $planning->heure_debut,
                'heure_fin'      => $planning->heure_fin,
                'pause_minutes'  => $planning->pause_minutes,
                'user'           => [
                    'id'    => $planning->user->id,
                    'nom'   => $planning->user->nom,
                    'email' => $planning->user->email,
                ],
                'equipe'         => $planning->user->equipe?->nom,
                'taches'         => $planning->taches->map(fn($t) => [
                    'id'    => $t->id,
                    'titre' => $t->titre,
                ]),
            ];
        });

        return response()->json($plannings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'date'          => 'required|date',
            'shift'         => 'required|in:J,J+,A,N,HO,R',
            'heure_debut'   => 'required|date_format:H:i',
            'heure_fin'     => 'required|date_format:H:i|after:heure_debut',
            'pause_minutes' => 'required|integer|min:0',
        ]);

        // Check: user ma-3nduch planning f-nafs date + shift
        $exists = Planning::where('user_id', $request->user_id)
                          ->where('date',    $request->date)
                          ->where('shift',   $request->shift)
                          ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Had employe 3ndu planning deja f-had date w shift'
            ], 422);
        }

        $planning = Planning::create([
            'user_id'       => $request->user_id,
            'date'          => $request->date,
            'shift'         => $request->shift,
            'heure_debut'   => $request->heure_debut,
            'heure_fin'     => $request->heure_fin,
            'pause_minutes' => $request->pause_minutes,
        ]);

        // WhatsApp notification
        $user = User::find($request->user_id);
        $this->sendWhatsApp(
            "Planning jadid wajed!\n" .
            "Employe: {$user->nom}\n" .
            "Date: {$request->date}\n" .
            "Shift: {$request->shift}\n" .
            "Dkhol tchouf planning dyalek."
        );

        return response()->json([
            'message'  => 'Planning créé avec succès',
            'planning' => [
                'id'            => $planning->id,
                'user_id'       => $planning->user_id,
                'date'          => $planning->date,
                'shift'         => $planning->shift,
                'heure_debut'   => $planning->heure_debut,
                'heure_fin'     => $planning->heure_fin,
                'pause_minutes' => $planning->pause_minutes,
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $planning = Planning::findOrFail($id);

        $request->validate([
            'date'          => 'sometimes|date',
            'shift'         => 'sometimes|in:J,J+,A,N,HO,R',
            'heure_debut'   => 'sometimes|date_format:H:i',
            'heure_fin'     => 'sometimes|date_format:H:i|after:heure_debut',
            'pause_minutes' => 'sometimes|integer|min:0',
        ]);

        // Check date + shift — ignore current planning
        if ($request->filled('date') || $request->filled('shift')) {
            $exists = Planning::where('user_id', $planning->user_id)
                              ->where('date',    $request->date    ?? $planning->date)
                              ->where('shift',   $request->shift   ?? $planning->shift)
                              ->where('id',      '!=', $id)
                              ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Planning kayna deja f-had date w shift'
                ], 422);
            }
        }

        $planning->update($request->only([
            'date', 'shift', 'heure_debut', 'heure_fin', 'pause_minutes'
        ]));

        // WhatsApp — planning tbddel
        $this->sendWhatsApp(
            "Planning tbddel!\n" .
            "Employe: {$planning->user->nom}\n" .
            "Date: {$planning->date}\n" .
            "Shift: {$planning->shift}\n" .
            "Dkhol tchouf planning dyalek."
        );

        return response()->json([
            'message'  => 'Planning modifié avec succès',
            'planning' => [
                'id'            => $planning->id,
                'date'          => $planning->date,
                'shift'         => $planning->shift,
                'heure_debut'   => $planning->heure_debut,
                'heure_fin'     => $planning->heure_fin,
                'pause_minutes' => $planning->pause_minutes,
            ]
        ]);
    }

    public function destroy($id)
    {
        $planning = Planning::findOrFail($id);
        $planning->delete();

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
                         ->whereHas('user', function ($q) use ($equipeId) {
                             $q->where('equipe_id', $equipeId);
                         });

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('shift')) {
            $query->where('shift', $request->shift);
        }

        $plannings = $query->get()->map(function ($planning) use ($user) {
            return [
                'id'            => $planning->id,
                'date'          => $planning->date,
                'shift'         => $planning->shift,
                'heure_debut'   => $planning->heure_debut,
                'heure_fin'     => $planning->heure_fin,
                'pause_minutes' => $planning->pause_minutes,
                'est_le_mien'   => $planning->user_id === $user->id,
                'user'          => [
                    'id'  => $planning->user->id,
                    'nom' => $planning->user->nom,
                ],
                'taches'        => $planning->taches->map(fn($t) => [
                    'id'    => $t->id,
                    'titre' => $t->titre,
                ]),
            ];
        });

        return response()->json($plannings);
    }

    // ==================
    // TACHES D-PLANNING
    // ==================

    public function addTache(Request $request, $id)
    {
        $planning = Planning::findOrFail($id);

        $request->validate([
            'tache_id' => 'required|exists:taches,id',
        ]);

        // Check: tache machi deja f-planning
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

        // Check: tache kayna f-planning
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
}
