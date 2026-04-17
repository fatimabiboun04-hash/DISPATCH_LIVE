<?php

namespace App\Http\Controllers;

use App\Models\Repo;
use App\Models\User;
use App\Notifications\ReposSoumisNotification;
use App\Notifications\ReposReponseNotification;
use Illuminate\Http\Request;

class ReposController extends Controller
{
    public function index(Request $request)
    {
        $query = Repo::with('user.equipe');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $repos = $query->paginate(10);

        return response()->json([
            'data'         => collect($repos->items())->map(fn($repo) => [
                'id'         => $repo->id,
                'motif'      => $repo->motif,
                'duree'      => $repo->duree,
                'statut'     => $repo->statut,
                'created_at' => $repo->created_at,
                'user'       => [
                    'id'     => $repo->user->id,
                    'nom'    => $repo->user->nom,
                    'email'  => $repo->user->email,
                    'equipe' => $repo->user->equipe?->nom,
                ],
            ]),
            'total'        => $repos->total(),
            'per_page'     => $repos->perPage(),
            'current_page' => $repos->currentPage(),
            'last_page'    => $repos->lastPage(),
        ]);
    }

    public function valider($id)
    {
        $repo = Repo::with('user')->findOrFail($id);

        if ($repo->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Demande deja traitée'
            ], 422);
        }

        $repo->update(['statut' => 'accepte']);
        $repo->user->notify(new ReposReponseNotification($repo, 'accepte'));

        return response()->json([
            'message' => 'Demande acceptée — email envoyé'
        ]);
    }

    public function refuser($id)
    {
        $repo = Repo::with('user')->findOrFail($id);

        if ($repo->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Demande deja traitée'
            ], 422);
        }

        $repo->update(['statut' => 'refuse']);
        $repo->user->notify(new ReposReponseNotification($repo, 'refuse'));

        return response()->json([
            'message' => 'Demande refusée — email envoyé'
        ]);
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()
            ->unreadNotifications
            ->map(fn($n) => [
                'id'         => $n->id,
                'message'    => $n->data['message'],
                'repo_id'    => $n->data['repo_id'],
                'user_nom'   => $n->data['user_nom'],
                'motif'      => $n->data['motif'],
                'duree'      => $n->data['duree'],
                'created_at' => $n->created_at,
            ]);

        return response()->json($notifications);
    }

    public function markAsRead($notificationId, Request $request)
    {
        $request->user()
                ->notifications()
                ->where('id', $notificationId)
                ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notification marquée comme lue'
        ]);
    }

    public function mesDemandes(Request $request)
    {
        $query = Repo::where('user_id', $request->user()->id);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $repos = $query->paginate(10);

        return response()->json([
            'data'         => collect($repos->items())->map(fn($repo) => [
                'id'         => $repo->id,
                'motif'      => $repo->motif,
                'duree'      => $repo->duree,
                'statut'     => $repo->statut,
                'created_at' => $repo->created_at,
            ]),
            'total'        => $repos->total(),
            'per_page'     => $repos->perPage(),
            'current_page' => $repos->currentPage(),
            'last_page'    => $repos->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'motif' => 'required|string|max:255',
            'duree' => 'required|integer|min:1|max:30',
        ]);

        $repo = Repo::create([
            'user_id' => $request->user()->id,
            'motif'   => $request->motif,
            'duree'   => $request->duree,
            'statut'  => 'en_attente',
        ]);

        $repo->load('user');

        $admins = User::where('role', 'Admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new ReposSoumisNotification($repo));
        }

        return response()->json([
            'message' => 'Demande soumise avec succès',
            'repo'    => [
                'id'     => $repo->id,
                'motif'  => $repo->motif,
                'duree'  => $repo->duree,
                'statut' => $repo->statut,
            ]
        ], 201);
    }
}