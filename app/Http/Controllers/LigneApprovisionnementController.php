<?php

namespace App\Http\Controllers;

use App\Models\ligne_approvisionnements;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LigneApprovisionnementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $items = ligne_approvisionnements::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des lignes d\'approvisionnement récupérée',
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Liste complète des lignes d\'approvisionnement récupérée',
            'data' => ligne_approvisionnements::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = ligne_approvisionnements::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Ligne d\'approvisionnement récupérée avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne d\'approvisionnement non trouvée'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_approvisionnement' => 'required|integer|exists:approvisionnements,id',
            'id_produit' => 'required|integer|exists:produits,id',
            'quantite' => 'required|integer',
            'prix_unitaire' => 'required|numeric',
            'prix_vente' => 'nullable|numeric',
            'id_devise' => 'required|integer|exists:devises,id',
        ]);

        $item = ligne_approvisionnements::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ligne d\'approvisionnement créée avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = ligne_approvisionnements::findOrFail($id);

            $validated = $request->validate([
                'id_approvisionnement' => 'required|integer|exists:approvisionnements,id',
                'id_produit' => 'required|integer|exists:produits,id',
                'quantite' => 'required|integer',
                'prix_unitaire' => 'required|numeric',
                'prix_vente' => 'nullable|numeric',
                'id_devise' => 'required|integer|exists:devises,id',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Ligne d\'approvisionnement mise à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne d\'approvisionnement non trouvée'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = ligne_approvisionnements::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ligne d\'approvisionnement supprimée avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne d\'approvisionnement non trouvée'], 404);
        }
    }
}
