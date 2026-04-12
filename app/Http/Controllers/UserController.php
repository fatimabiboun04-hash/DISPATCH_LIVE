<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('equipe')->where('role', 'NoAdmin');

        if ($request->filled('search')) {
            $query->where('nom', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('equipe_id')) {
            $query->where('equipe_id', $request->equipe_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->paginate(10);

        return response()->json([
            'data'         => collect($users->items())->map(fn($user) => [
                'id'          => $user->id,
                'nom'         => $user->nom,
                'email'       => $user->email,
                'equipe'      => $user->equipe?->nom,
                'equipe_id'   => $user->equipe_id,
                'rating'      => $user->rating,
                'description' => $user->description,
                'is_active'   => $user->is_active,
            ]),
            'total'        => $users->total(),
            'per_page'     => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page'    => $users->lastPage(),
        ]);
    }

    public function show($id)
    {
        $user = User::with('equipe')->findOrFail($id);

        return response()->json([
            'id'          => $user->id,
            'nom'         => $user->nom,
            'email'       => $user->email,
            'equipe'      => $user->equipe?->nom,
            'equipe_id'   => $user->equipe_id,
            'rating'      => $user->rating,
            'description' => $user->description,
            'is_active'   => $user->is_active,
            'created_at'  => $user->created_at,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom'         => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'equipe_id'   => 'nullable|exists:equipes,id',
            'rating'      => 'nullable|integer|min:-5|max:5',
            'description' => 'nullable|string',
        ]);

        $user = User::create([
            'nom'         => $request->nom,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => 'NoAdmin',
            'equipe_id'   => $request->equipe_id,
            'rating'      => $request->rating ?? 0,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return response()->json([
            'message' => 'Employe créé avec succès',
            'user'    => [
                'id'          => $user->id,
                'nom'         => $user->nom,
                'email'       => $user->email,
                'equipe_id'   => $user->equipe_id,
                'rating'      => $user->rating,
                'description' => $user->description,
                'is_active'   => $user->is_active,
            ]
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'nom'         => 'sometimes|string|max:255',
            'email'       => 'sometimes|email|unique:users,email,' . $id,
            'password'    => 'nullable|string|min:6',
            'equipe_id'   => 'sometimes|nullable|exists:equipes,id',
            'rating'      => 'nullable|integer|min:-5|max:5',
            'description' => 'nullable|string',
            'is_active'   => 'sometimes|boolean',
        ]);

        $data = $request->only([
            'nom', 'email', 'equipe_id',
            'rating', 'description', 'is_active'
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Employe modifié avec succès',
            'user'    => [
                'id'          => $user->id,
                'nom'         => $user->nom,
                'email'       => $user->email,
                'equipe'      => $user->equipe?->nom,
                'equipe_id'   => $user->equipe_id,
                'rating'      => $user->rating,
                'description' => $user->description,
                'is_active'   => $user->is_active,
            ]
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'Admin') {
            return response()->json([
                'message' => 'Ma-ymkn-ch tsupprimi Admin'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Employe supprimé avec succès'
        ]);
    }

    public function monProfil(Request $request)
    {
        $user = $request->user()->load('equipe');

        return response()->json([
            'id'          => $user->id,
            'nom'         => $user->nom,
            'email'       => $user->email,
            'equipe'      => $user->equipe?->nom,
            'rating'      => $user->rating,
            'description' => $user->description,
            'is_active'   => $user->is_active,
        ]);
    }
}
