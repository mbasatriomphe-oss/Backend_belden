<?php

namespace App\Http\Controllers;

use App\Models\transactions_caisses;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionCaisseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $items = transactions_caisses::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des transactions de caisse récupérée',
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
            'message' => 'Liste complète des transactions de caisse récupérée',
            'data' => transactions_caisses::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = transactions_caisses::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Transaction de caisse récupérée avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Transaction non trouvée'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_caisse' => 'required|integer|exists:caisses,id',
            'type' => ['required', Rule::in(['entree', 'sortie'])],
            'montant' => 'required|numeric',
            'reference_type' => 'required|string',
            'reference_id' => 'required|integer',
            'description' => 'nullable|string',
            'solde_avant' => 'required|numeric',
            'solde_apres' => 'required|numeric',
            'created_by' => 'nullable|integer|exists:users,id',
        ]);

        $item = transactions_caisses::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaction de caisse créée avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = transactions_caisses::findOrFail($id);

            $validated = $request->validate([
                'id_caisse' => 'required|integer|exists:caisses,id',
                'type' => ['required', Rule::in(['entree', 'sortie'])],
                'montant' => 'required|numeric',
                'reference_type' => 'required|string',
                'reference_id' => 'required|integer',
                'description' => 'nullable|string',
                'solde_avant' => 'required|numeric',
                'solde_apres' => 'required|numeric',
                'created_by' => 'nullable|integer|exists:users,id',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Transaction de caisse mise à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Transaction non trouvée'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = transactions_caisses::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transaction de caisse supprimée avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Transaction non trouvée'], 404);
        }
    }
}
