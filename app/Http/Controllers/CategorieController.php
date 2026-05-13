<?php
namespace App\Http\Controllers;

use App\Models\categories;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategorieController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = categories::query();

        if ($search) {
            $query->where('nom', 'like', "%{$search}%");
        }

        $categories = $query->orderBy('nom')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des catégories récupérée avec succès',
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $categories = categories::orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des catégories récupérée',
            'data' => $categories,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $categorie = categories::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie récupérée avec succès',
                'data' => $categorie,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Catégorie non trouvée'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'photo' => 'nullable|string',
        ]);

        $categorie = categories::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => $categorie,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $categorie = categories::findOrFail($id);

            $validated = $request->validate([
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'photo' => 'nullable|string',
            ]);

            $categorie->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'data' => $categorie,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Catégorie non trouvée'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $categorie = categories::findOrFail($id);
            $categorie->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Catégorie non trouvée'], 404);
        }
    }
}
