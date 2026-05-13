<?php

namespace App\Http\Controllers;

use App\Models\produits;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProduitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = produits::query();

        if ($search) {
            $query->where('code', 'like', "%{$search}%")
                ->orWhere('nom', 'like', "%{$search}%");
        }

        $produits = $query->orderBy('nom')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des produits récupérée avec succès',
            'data' => $produits->items(),
            'meta' => [
                'current_page' => $produits->currentPage(),
                'per_page' => $produits->perPage(),
                'total' => $produits->total(),
                'last_page' => $produits->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $produits = produits::orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des produits récupérée',
            'data' => $produits,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $produit = produits::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Produit récupéré avec succès',
                'data' => $produit,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:produits,code',
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
            'photo' => 'nullable|string',
            'unite_id' => 'required|integer|exists:unites,id',
            'categorie_id' => 'required|integer|exists:categories,id',
        ]);

        $produit = produits::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'data' => $produit,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $produit = produits::findOrFail($id);

            $validated = $request->validate([
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('produits', 'code')->ignore($id),
                ],
                'nom' => 'required|string|max:100',
                'description' => 'nullable|string',
                'photo' => 'nullable|string',
                'unite_id' => 'required|integer|exists:unites,id',
                'categorie_id' => 'required|integer|exists:categories,id',
            ]);

            $produit->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'data' => $produit,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $produit = produits::findOrFail($id);
            $produit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Produit non trouvé'], 404);
        }
    }
}
