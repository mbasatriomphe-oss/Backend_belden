<?php

namespace App\Http\Controllers;

use App\Models\retours;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RetourController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = retours::query();

        if ($search) {
            $query->where('code', 'like', "%{$search}%");
        }

        $items = $query->orderBy('date_retour', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des retours récupérée avec succès',
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
        $items = retours::orderBy('date_retour', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des retours récupérée',
            'data' => $items,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = retours::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Retour récupéré avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Retour non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:retours,code',
            'date_retour' => 'required|date',
            'id_vente' => 'required|integer|exists:ventes,id',
            'id_client' => 'required|integer|exists:clients,id',
            'id_vendeur' => 'required|integer|exists:vendeurs,id',
            'motif' => 'nullable|string',
            'commentaire' => 'nullable|string',
        ]);

        $item = retours::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Retour créé avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = retours::findOrFail($id);

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('retours', 'code')->ignore($id),
                ],
                'date_retour' => 'required|date',
                'id_vente' => 'required|integer|exists:ventes,id',
                'id_client' => 'required|integer|exists:clients,id',
                'id_vendeur' => 'required|integer|exists:vendeurs,id',
                'motif' => 'nullable|string',
                'commentaire' => 'nullable|string',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Retour mis à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Retour non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = retours::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Retour supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Retour non trouvé'], 404);
        }
    }
}
