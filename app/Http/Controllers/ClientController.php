<?php

namespace App\Http\Controllers;

use App\Models\clients;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = clients::query();

        if ($search) {
            $query->where('nom', 'like', "%{$search}%")
                ->orWhere('post_nom', 'like', "%{$search}%")
                ->orWhere('prenom', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%");
        }

        $clients = $query->orderBy('nom')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des clients récupérée avec succès',
            'data' => $clients->items(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'last_page' => $clients->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $clients = clients::orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des clients récupérée',
            'data' => $clients,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $client = clients::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Client récupéré avec succès',
                'data' => $client,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Client non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:90',
            'post_nom' => 'required|string|max:90',
            'prenom' => 'required|string|max:90',
            'adresse' => 'required|string|max:63',
            'ville' => 'required|string|max:50',
            'pays' => 'required|string|max:50',
            'contact' => 'required|string|max:50',
            'iduser' => 'nullable|integer|exists:users,id',
        ]);

        $client = clients::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Client créé avec succès',
            'data' => $client,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $client = clients::findOrFail($id);

            $validated = $request->validate([
                'nom' => 'required|string|max:90',
                'post_nom' => 'required|string|max:90',
                'prenom' => 'required|string|max:90',
                'adresse' => 'required|string|max:63',
                'ville' => 'required|string|max:50',
                'pays' => 'required|string|max:50',
                'contact' => 'required|string|max:50',
                'iduser' => 'nullable|integer|exists:users,id',
            ]);

            $client->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Client mis à jour avec succès',
                'data' => $client,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Client non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $client = clients::findOrFail($id);
            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Client non trouvé'], 404);
        }
    }
}
