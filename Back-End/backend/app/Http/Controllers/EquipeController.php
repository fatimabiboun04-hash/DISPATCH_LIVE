<?php

namespace App\Http\Controllers;

use App\Models\Equipe;
use Illuminate\Http\Request;

class EquipeController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipe::withCount('users');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $equipes = $query->paginate(10);

        return response()->json([
            'data'         => collect($equipes->items())->map(fn($equipe) => [
                'id'          => $equipe->id,
                'nom'         => $equipe->nom,
                'type'        => $equipe->type,
                'users_count' => $equipe->users_count,
            ]),
            'total'        => $equipes->total(),
            'per_page'     => $equipes->perPage(),
            'current_page' => $equipes->currentPage(),
            'last_page'    => $equipes->lastPage(),
        ]);
    }

    public function show($id)
    {
        $equipe = Equipe::with('users')->findOrFail($id);

        return response()->json([
            'id'    => $equipe->id,
            'nom'   => $equipe->nom,
            'type'  => $equipe->type,
            'users' => collect($equipe->users)->map(fn($u) => [
                'id'        => $u->id,
                'nom'       => $u->nom,
                'email'     => $u->email,
                'rating'    => $u->rating,
                'is_active' => $u->is_active,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'  => 'required|string|max:255|unique:equipes,nom',
            'type' => 'required|in:Fixe,Mobile',
        ]);

        $equipe = Equipe::create([
            'nom'  => $request->nom,
            'type' => $request->type,
        ]);

        return response()->json([
            'message' => 'Equipe créée avec succès',
            'equipe'  => $equipe
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $equipe = Equipe::findOrFail($id);

        $request->validate([
            'nom'  => 'sometimes|string|max:255|unique:equipes,nom,' . $id,
            'type' => 'sometimes|in:Fixe,Mobile',
        ]);

        $equipe->update($request->only(['nom', 'type']));

        return response()->json([
            'message' => 'Equipe modifiée avec succès',
            'equipe'  => [
                'id'   => $equipe->id,
                'nom'  => $equipe->nom,
                'type' => $equipe->type,
            ]
        ]);
    }

    public function destroy($id)
    {
        $equipe = Equipe::withCount('users')->findOrFail($id);

        if ($equipe->users_count > 0) {
            return response()->json([
                'message' => 'Ma-ymkn-ch tsupprimi — equipe fiha ' . $equipe->users_count . ' employes'
            ], 422);
        }

        $equipe->delete();

        return response()->json([
            'message' => 'Equipe supprimée avec succès'
        ]);
    }
}
