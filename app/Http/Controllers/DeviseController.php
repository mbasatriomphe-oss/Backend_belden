<?php

namespace App\Http\Controllers;

use App\Models\devise;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = devise::query();

        if ($search) {
            $query->where('code', 'like', "%{$search}%")
                ->orWhere('nom', 'like', "%{$search}%")
                ->orWhere('symbole', 'like', "%{$search}%");
        }

        $devises = $query->orderBy('code')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des devises récupérée avec succès',
            'data' => $devises->items(),
            'meta' => [
                'current_page' => $devises->currentPage(),
                'per_page' => $devises->perPage(),
                'total' => $devises->total(),
                'last_page' => $devises->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $devises = devise::orderBy('code')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des devises récupérée',
            'data' => $devises,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $devise = devise::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Devise récupérée avec succès',
                'data' => $devise,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Devise non trouvée'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:4|unique:devises,code',
            'nom' => 'required|string|max:50',
            'symbole' => 'required|string|max:10',
        ]);

        $devise = devise::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Devise créée avec succès',
            'data' => $devise,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $devise = devise::findOrFail($id);

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:4',
                    Rule::unique('devises', 'code')->ignore($id),
                ],
                'nom' => 'required|string|max:50',
                'symbole' => 'required|string|max:10',
            ]);

            $devise->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Devise mise à jour avec succès',
                'data' => $devise,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Devise non trouvée'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $devise = devise::findOrFail($id);
            $devise->delete();

            return response()->json([
                'success' => true,
                'message' => 'Devise supprimée avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Devise non trouvée'], 404);
        }
    }
}
