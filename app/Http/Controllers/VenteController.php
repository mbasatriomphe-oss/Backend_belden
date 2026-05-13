<?php

namespace App\Http\Controllers;

use App\Models\ventes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VenteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = ventes::query();

        if ($search) {
            $query->where('code', 'like', "%{$search}%");
        }

        $items = $query->orderBy('date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des ventes récupérée avec succès',
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
        $items = ventes::orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des ventes récupérée',
            'data' => $items,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = ventes::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Vente récupérée avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Vente non trouvée'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:ventes,code',
            'date' => 'required|date',
            'id_vendeur' => 'required|integer|exists:vendeurs,id',
            'id_client' => 'required|integer|exists:clients,id',
        ]);

        $item = ventes::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vente créée avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = ventes::findOrFail($id);

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('ventes', 'code')->ignore($id),
                ],
                'date' => 'required|date',
                'id_vendeur' => 'required|integer|exists:vendeurs,id',
                'id_client' => 'required|integer|exists:clients,id',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Vente mise à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Vente non trouvée'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = ventes::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vente supprimée avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Vente non trouvée'], 404);
        }
    }
}
