<?php

namespace App\Http\Controllers;

use App\Models\lots;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = lots::query();

        if ($search) {
            $query->where('numero_lot', 'like', "%{$search}%");
        }

        $items = $query->orderBy('date_reception', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des lots récupérée avec succès',
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
        $items = lots::orderBy('date_reception', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des lots récupérée',
            'data' => $items,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = lots::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Lot récupéré avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Lot non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_lot' => 'required|string|max:50|unique:lots,numero_lot',
            'id_produit' => 'required|integer|exists:produits,id',
            'id_approvisionnement' => 'required|integer|exists:approvisionnements,id',
            'id_ligne_approvisionnement' => 'required|integer|exists:ligne_approvisionnements,id',
            'quantite_initial' => 'required|integer',
            'date_reception' => 'required|date',
            'date_expiration' => 'nullable|date',
            'id_devise' => 'required|integer|exists:devises,id',
        ]);

        $item = lots::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lot créé avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = lots::findOrFail($id);

            $validated = $request->validate([
                'numero_lot' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('lots', 'numero_lot')->ignore($id),
                ],
                'id_produit' => 'required|integer|exists:produits,id',
                'id_approvisionnement' => 'required|integer|exists:approvisionnements,id',
                'id_ligne_approvisionnement' => 'required|integer|exists:ligne_approvisionnements,id',
                'quantite_initial' => 'required|integer',
                'date_reception' => 'required|date',
                'date_expiration' => 'nullable|date',
                'id_devise' => 'required|integer|exists:devises,id',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Lot mis à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Lot non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = lots::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lot supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Lot non trouvé'], 404);
        }
    }
}
