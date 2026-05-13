<?php

namespace App\Http\Controllers;

use App\Models\fournisseurs;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = fournisseurs::query();

        if ($search) {
            $query->where('nom', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%")
                ->orWhere('pays', 'like', "%{$search}%")
                ->orWhere('contact', 'like', "%{$search}%");
        }

        $fournisseurs = $query->orderBy('nom')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des fournisseurs récupérée avec succès',
            'data' => $fournisseurs->items(),
            'meta' => [
                'current_page' => $fournisseurs->currentPage(),
                'per_page' => $fournisseurs->perPage(),
                'total' => $fournisseurs->total(),
                'last_page' => $fournisseurs->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $fournisseurs = fournisseurs::orderBy('nom')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complète des fournisseurs récupérée',
            'data' => $fournisseurs,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $fournisseur = fournisseurs::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur récupéré avec succès',
                'data' => $fournisseur,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Fournisseur non trouvé'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:90',
            'adresse' => 'required|string|max:63',
            'ville' => 'required|string|max:50',
            'pays' => 'required|string|max:50',
            'contact' => 'required|string|max:50',
        ]);

        $fournisseur = fournisseurs::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Fournisseur créé avec succès',
            'data' => $fournisseur,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $fournisseur = fournisseurs::findOrFail($id);

            $validated = $request->validate([
                'nom' => 'required|string|max:90',
                'adresse' => 'required|string|max:63',
                'ville' => 'required|string|max:50',
                'pays' => 'required|string|max:50',
                'contact' => 'required|string|max:50',
            ]);

            $fournisseur->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur mis à jour avec succès',
                'data' => $fournisseur,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Fournisseur non trouvé'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $fournisseur = fournisseurs::findOrFail($id);
            $fournisseur->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur supprimé avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Fournisseur non trouvé'], 404);
        }
    }
}
