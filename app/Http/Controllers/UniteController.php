<?php

namespace App\Http\Controllers;

use App\Models\Unite;  // ChangÃ© : U majuscule
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UniteController extends Controller
{
    /**
     * GET /api/unites
     * Liste toutes les unitÃ©s avec pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');
            
            $query = Unite::query();  // ChangÃ© : Unite avec U majuscule
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nom', 'like', "%{$search}%")
                      ->orWhere('symbole', 'like', "%{$search}%");
                });
            }
            
            $unites = $query->orderBy('nom')->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'message' => 'Liste des unitÃ©s rÃ©cupÃ©rÃ©e avec succÃ¨s',
                'data' => $unites->items(),
                'meta' => [
                    'current_page' => $unites->currentPage(),
                    'per_page' => $unites->perPage(),
                    'total' => $unites->total(),
                    'last_page' => $unites->lastPage(),
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ©cupÃ©ration des unitÃ©s',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/unites/all
     * Liste toutes les unitÃ©s (sans pagination)
     */
    public function all(): JsonResponse
    {
        try {
            $unites = Unite::orderBy('nom')  // ChangÃ© : Unite avec U majuscule
                ->get(['id', 'nom', 'symbole']);
            
            return response()->json([
                'success' => true,
                'message' => 'Liste complÃ¨te des unitÃ©s rÃ©cupÃ©rÃ©e',
                'data' => $unites
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ©cupÃ©ration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/unites/{id}
     * Afficher une unitÃ© spÃ©cifique
     */
    public function show($id): JsonResponse
    {
        try {
            $unite = Unite::findOrFail($id);  // ChangÃ© : Unite avec U majuscule
            
            return response()->json([
                'success' => true,
                'message' => 'UnitÃ© rÃ©cupÃ©rÃ©e avec succÃ¨s',
                'data' => $unite
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'UnitÃ© non trouvÃ©e'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la rÃ©cupÃ©ration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/unites
     * CrÃ©er une nouvelle unitÃ©
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nom' => 'required|string|max:255|unique:unites,nom',
                'symbole' => 'required|string|max:10|unique:unites,symbole',
            ]);
            
            $unite = Unite::create($validated);  // ChangÃ© : Unite avec U majuscule
            
            return response()->json([
                'success' => true,
                'message' => 'UnitÃ© crÃ©Ã©e avec succÃ¨s',
                'data' => $unite
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la crÃ©ation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT/PATCH /api/unites/{id}
     * Mettre Ã  jour une unitÃ©
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $unite = Unite::findOrFail($id);  // ChangÃ© : Unite avec U majuscule
            
            $validated = $request->validate([
                'nom' => [
                    'required',
                    'string',
                    'max:255',
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
                'message' => 'UnitÃ© mise Ã  jour avec succÃ¨s',
                'data' => $unite
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'UnitÃ© non trouvÃ©e'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise Ã  jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DELETE /api/unites/{id}
     * Supprimer une unitÃ©
     */
    public function destroy($id): JsonResponse
    {
        try {
            $unite = Unite::findOrFail($id);  // ChangÃ© : Unite avec U majuscule
            $unite->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'UnitÃ© supprimÃ©e avec succÃ¨s'
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'UnitÃ© non trouvÃ©e'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}