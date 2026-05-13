<?php

namespace App\Http\Controllers;

use App\Models\vendeurs;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendeurController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = vendeurs::query();

        if ($search) {
            $query->where('nom', 'like', "%{$search}%")
                ->orWhere('prenom', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        $vendeurs = $query->orderBy('nom')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des vendeurs récupérée avec succès',
            'data' => $vendeurs->items(),
            'meta' => [
                'current_page' => $vendeurs->currentPage(),
                'per_page' => $vendeurs->perPage(),
                'total' => $vendeurs->total(),
                'last_page' => $vendeurs->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $vendeurs = vendeurs::orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des vendeurs récupérée',
            'data' => $vendeurs,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $vendeur = vendeurs::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Vendeur récupéré avec succès',
                'data' => $vendeur,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Vendeur non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:vendeurs,code',
            'email' => 'required|string|email|max:255|unique:vendeurs,email',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
        ]);

        $vendeur = vendeurs::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendeur créé avec succès',
            'data' => $vendeur,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $vendeur = vendeurs::findOrFail($id);

            $validated = $request->validate([
                'nom' => 'required|string|max:100',
                'prenom' => 'required|string|max:100',
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('vendeurs', 'code')->ignore($id),
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('vendeurs', 'email')->ignore($id),
                ],
                'telephone' => 'nullable|string|max:20',
                'adresse' => 'nullable|string',
            ]);

            $vendeur->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Vendeur mis à jour avec succès',
                'data' => $vendeur,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Vendeur non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $vendeur = vendeurs::findOrFail($id);
            $vendeur->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vendeur supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Vendeur non trouvé'], 404);
        }
    }
}
