<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\unites;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class UniteController extends Controller
{
    /**
     * GET /api/unites
     * Liste toutes les unités (avec pagination optionnelle)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');
        
        $query = unites::query();
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('symbole', 'like', "%{$search}%");
            });
        }
        
        $unites = $query->orderBy('nom')->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $unites->items(),
            'meta' => [
                'current_page' => $unites->currentPage(),
                'per_page' => $unites->perPage(),
                'total' => $unites->total(),
                'last_page' => $unites->lastPage()
            ]
        ]);
    }

    /**
     * GET /api/unites/all
     * Liste toutes les unités (sans pagination, pour selects)
     */
    public function all(): JsonResponse
    {
        $unites = unites::orderBy('nom')->get(['id', 'nom', 'symbole']);
        
        return response()->json([
            'success' => true,
            'data' => $unites
        ]);
    }

    /**
     * GET /api/unites/{id}
     * Afficher une unité spécifique
     */
    public function show($id): JsonResponse
    {
        $unite = unites::find($id);
        
        if (!$unite) {
            return response()->json([
                'success' => false,
                'message' => 'Unité non trouvée'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $unite
        ]);
    }

    /**
     * POST /api/unites
     * Créer une nouvelle unité
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100|unique:unites,nom',
            'symbole' => 'required|string|max:10|unique:unites,symbole',
        ]);

        $unite = unites::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Unité créée avec succès',
            'data' => $unite
        ], 201);
    }

    /**
     * PUT/PATCH /api/unites/{id}
     * Mettre à jour une unité
     */
    public function update(Request $request, $id): JsonResponse
    {
        $unite = unites::find($id);
        
        if (!$unite) {
            return response()->json([
                'success' => false,
                'message' => 'Unité non trouvée'
            ], 404);
        }
        
        $validated = $request->validate([
            'nom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('unites', 'nom')->ignore($id)
            ],
            'symbole' => [
                'required',
                'string',
                'max:10',
                Rule::unique('unites', 'symbole')->ignore($id)
            ],
        ]);
        
        $unite->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Unité mise à jour avec succès',
            'data' => $unite
        ]);
    }

    /**
     * DELETE /api/unites/{id}
     * Supprimer une unité
     */
    public function destroy($id): JsonResponse
    {
        $unite = unites::find($id);
        
        if (!$unite) {
            return response()->json([
                'success' => false,
                'message' => 'Unité non trouvée'
            ], 404);
        }
        
        // Vérifier si l'unité est utilisée dans d'autres tables
        // (à adapter selon votre structure)
        // if ($unite->produits()->count() > 0) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Cette unité ne peut pas être supprimée car elle est utilisée par des produits'
        //     ], 409);
        // }
        
        $unite->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Unité supprimée avec succès'
        ]);
    }
}