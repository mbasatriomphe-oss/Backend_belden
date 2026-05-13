<?php

namespace App\Http\Controllers;

use App\Models\taux;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TauxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = taux::query();

        if ($search) {
            $query->where('statut', 'like', "%{$search}%");
        }

        $tauxes = $query->orderBy('date_effet', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des taux récupérée avec succès',
            'data' => $tauxes->items(),
            'meta' => [
                'current_page' => $tauxes->currentPage(),
                'per_page' => $tauxes->perPage(),
                'total' => $tauxes->total(),
                'last_page' => $tauxes->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $tauxes = taux::orderBy('date_effet', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des taux récupérée',
            'data' => $tauxes,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $taux = taux::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Taux récupéré avec succès',
                'data' => $taux,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Taux non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'devise_source' => 'required|integer|exists:devises,id',
            'devise_but' => 'required|integer|exists:devises,id',
            'valeur' => 'required|numeric',
            'date_effet' => 'required|date',
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ]);

        $t = taux::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Taux créé avec succès',
            'data' => $t,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $t = taux::findOrFail($id);

            $validated = $request->validate([
                'devise_source' => 'required|integer|exists:devises,id',
                'devise_but' => 'required|integer|exists:devises,id',
                'valeur' => 'required|numeric',
                'date_effet' => 'required|date',
                'statut' => ['required', Rule::in(['actif', 'inactif'])],
            ]);

            $t->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Taux mis à jour avec succès',
                'data' => $t,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Taux non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $t = taux::findOrFail($id);
            $t->delete();

            return response()->json([
                'success' => true,
                'message' => 'Taux supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Taux non trouvé'], 404);
        }
    }
}
