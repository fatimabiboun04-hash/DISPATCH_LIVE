<?php

namespace App\Http\Controllers;

use App\Models\Tache;
use Illuminate\Http\Request;

class TacheController extends Controller
{
    public function index(Request $request)
    {
        $query = Tache::query();

        if ($request->filled('is_permanent')) {
            $query->where('is_permanent', $request->boolean('is_permanent'));
        }

        $taches = $query->paginate(10);

        return response()->json([
            'data'         => collect($taches->items())->map(fn($tache) => [
                'id'           => $tache->id,
                'titre'        => $tache->titre,
                'description'  => $tache->description,
                'is_permanent' => $tache->is_permanent,
            ]),
            'total'        => $taches->total(),
            'per_page'     => $taches->perPage(),
            'current_page' => $taches->currentPage(),
            'last_page'    => $taches->lastPage(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre'        => 'required|string|max:255|unique:taches,titre',
            'description'  => 'required|string',
            'is_permanent' => 'nullable|boolean',
        ]);

        $tache = Tache::create([
            'titre'        => $request->titre,
            'description'  => $request->description,
            'is_permanent' => $request->is_permanent ?? false,
        ]);

        return response()->json([
            'message' => 'Tache créée avec succès',
            'tache'   => [
                'id'           => $tache->id,
                'titre'        => $tache->titre,
                'description'  => $tache->description,
                'is_permanent' => $tache->is_permanent,
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $tache = Tache::findOrFail($id);

        $request->validate([
            'titre'        => 'sometimes|string|max:255|unique:taches,titre,' . $id,
            'description'  => 'sometimes|string',
            'is_permanent' => 'nullable|boolean',
        ]);

        $tache->update($request->only([
            'titre', 'description', 'is_permanent'
        ]));

        return response()->json([
            'message' => 'Tache modifiée avec succès',
            'tache'   => [
                'id'           => $tache->id,
                'titre'        => $tache->titre,
                'description'  => $tache->description,
                'is_permanent' => $tache->is_permanent,
            ]
        ]);
    }

    public function destroy($id)
    {
        $tache = Tache::findOrFail($id);

        if ($tache->plannings()->exists()) {
            return response()->json([
                'message' => 'Ma-ymkn-ch tsupprimi — tache msta3mla f-planning'
            ], 422);
        }

        $tache->delete();

        return response()->json([
            'message' => 'Tache supprimée avec succès'
        ]);
    }
}
