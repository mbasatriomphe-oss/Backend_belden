<?php

namespace App\Http\Controllers;

use App\Models\mouvements_stock_fifos;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MouvementStockFifoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $items = mouvements_stock_fifos::orderBy('date_mouvement', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des mouvements de stock récupérée',
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
            'message' => 'Liste complète des mouvements de stock récupérée',
            'data' => mouvements_stock_fifos::orderBy('date_mouvement', 'desc')->get(),
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = mouvements_stock_fifos::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Mouvement de stock récupéré avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Mouvement non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_lot' => 'required|integer|exists:lots,id',
            'id_ligne_vente' => 'nullable|integer|exists:ligne_ventes,id',
            'id_ligne_retour' => 'nullable|integer|exists:ligne_retours,id',
            'type_mouvement' => ['required', Rule::in(['entree', 'sortie', 'retour'])],
            'quantite' => 'required|integer',
            'quantite_restante_avant' => 'required|integer',
            'quantite_restante_apres' => 'required|integer',
            'date_mouvement' => 'required|date',
        ]);

        $item = mouvements_stock_fifos::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mouvement de stock créé avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = mouvements_stock_fifos::findOrFail($id);

            $validated = $request->validate([
                'id_lot' => 'required|integer|exists:lots,id',
                'id_ligne_vente' => 'nullable|integer|exists:ligne_ventes,id',
                'id_ligne_retour' => 'nullable|integer|exists:ligne_retours,id',
                'type_mouvement' => ['required', Rule::in(['entree', 'sortie', 'retour'])],
                'quantite' => 'required|integer',
                'quantite_restante_avant' => 'required|integer',
                'quantite_restante_apres' => 'required|integer',
                'date_mouvement' => 'required|date',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Mouvement de stock mis à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Mouvement non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = mouvements_stock_fifos::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mouvement de stock supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Mouvement non trouvé'], 404);
        }
    }
}
