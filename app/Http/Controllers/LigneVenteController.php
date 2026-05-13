<?php

namespace App\Http\Controllers;

use App\Models\ligne_ventes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LigneVenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $items = ligne_ventes::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des lignes de vente récupérée',
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
            'message' => 'Liste complète des lignes de vente récupérée',
            'data' => ligne_ventes::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = ligne_ventes::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Ligne de vente récupérée avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne de vente non trouvée'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_vente' => 'required|integer|exists:ventes,id',
            'id_produit' => 'required|integer|exists:produits,id',
            'quantite' => 'required|integer',
            'prix_vente' => 'required|numeric',
            'id_devise' => 'required|integer|exists:devises,id',
        ]);

        $item = ligne_ventes::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ligne de vente créée avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = ligne_ventes::findOrFail($id);

            $validated = $request->validate([
                'id_vente' => 'required|integer|exists:ventes,id',
                'id_produit' => 'required|integer|exists:produits,id',
                'quantite' => 'required|integer',
                'prix_vente' => 'required|numeric',
                'id_devise' => 'required|integer|exists:devises,id',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Ligne de vente mise à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne de vente non trouvée'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = ligne_ventes::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ligne de vente supprimée avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne de vente non trouvée'], 404);
        }
    }
}
