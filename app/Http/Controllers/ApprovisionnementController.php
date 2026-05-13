<?php

namespace App\Http\Controllers;

use App\Models\approvisionnements;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovisionnementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = approvisionnements::query();

        if ($search) {
            $query->where('code', 'like', "%{$search}%");
        }

        $items = $query->orderBy('date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des approvisionnements récupérée avec succès',
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
        $items = approvisionnements::orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des approvisionnements récupérée',
            'data' => $items,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = approvisionnements::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Approvisionnement récupéré avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Approvisionnement non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:approvisionnements,code',
            'date' => 'required|date',
            'id_user' => 'required|integer|exists:users,id',
            'id_fournisseur' => 'required|integer|exists:fournisseurs,id',
        ]);

        $item = approvisionnements::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Approvisionnement créé avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = approvisionnements::findOrFail($id);

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('approvisionnements', 'code')->ignore($id),
                ],
                'date' => 'required|date',
                'id_user' => 'required|integer|exists:users,id',
                'id_fournisseur' => 'required|integer|exists:fournisseurs,id',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Approvisionnement mis à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Approvisionnement non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = approvisionnements::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Approvisionnement supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Approvisionnement non trouvé'], 404);
        }
    }
}
