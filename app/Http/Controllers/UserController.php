<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Afficher la liste des utilisateurs
     */
    public function index()
    {
        $users = User::all();
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'post_nom' => 'nullable|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['user', 'admin'])]
        ]);

        $user = User::create([
            'nom' => $validated['nom'],
            'post_nom' => $validated['post_nom'] ?? null,
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role']
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur créé avec succès',
            'data' => $user
        ], 201);
    }

    /**
     * Afficher un utilisateur spécifique
     */
    public function show(User $user)
    {
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'post_nom' => 'nullable|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'role' => ['sometimes', Rule::in(['user', 'admin'])]
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => $user
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }
}