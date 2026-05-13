<?php

namespace App\Http\Controllers;

use App\Models\ligne_retours;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LigneRetourController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $items = ligne_retours::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des lignes de retour récupérée',
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
        return response()->json([
            'success' => true,
            'message' => 'Liste complète des lignes de retour récupérée',
            'data' => ligne_retours::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = ligne_retours::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Ligne de retour récupérée avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne de retour non trouvée'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_retour' => 'required|integer|exists:retours,id',
            'id_produit' => 'required|integer|exists:produits,id',
            'id_ligne_vente' => 'required|integer|exists:ligne_ventes,id',
            'id_lot' => 'required|integer|exists:lots,id',
            'quantite_retournee' => 'required|integer',
            'prix_vente_original' => 'required|numeric',
            'prix_remboursement' => 'required|numeric',
            'montant_penalite' => 'nullable|numeric',
            'prix_unitaire_lot' => 'required|numeric',
            'raison_difference' => ['required', Rule::in(['aucune','usage_client','deballage','decote_naturelle','promotion_remplacement','penalite_contrat','autre'])],
            'justification_difference' => 'nullable|string',
            'etat_produit' => ['required', Rule::in(['bon','lege_usage','endommage','defectueux','usage','emballage_ouvert'])],
            'reintegre_stock' => 'required|boolean',
            'id_devise' => 'required|integer|exists:devises,id',
        ]);

        $item = ligne_retours::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ligne de retour créée avec succès',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = ligne_retours::findOrFail($id);

            $validated = $request->validate([
                'id_retour' => 'required|integer|exists:retours,id',
                'id_produit' => 'required|integer|exists:produits,id',
                'id_ligne_vente' => 'required|integer|exists:ligne_ventes,id',
                'id_lot' => 'required|integer|exists:lots,id',
                'quantite_retournee' => 'required|integer',
                'prix_vente_original' => 'required|numeric',
                'prix_remboursement' => 'required|numeric',
                'montant_penalite' => 'nullable|numeric',
                'prix_unitaire_lot' => 'required|numeric',
                'raison_difference' => ['required', Rule::in(['aucune','usage_client','deballage','decote_naturelle','promotion_remplacement','penalite_contrat','autre'])],
                'justification_difference' => 'nullable|string',
                'etat_produit' => ['required', Rule::in(['bon','lege_usage','endommage','defectueux','usage','emballage_ouvert'])],
                'reintegre_stock' => 'required|boolean',
                'id_devise' => 'required|integer|exists:devises,id',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Ligne de retour mise à jour avec succès',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne de retour non trouvée'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = ligne_retours::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ligne de retour supprimée avec succès',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Ligne de retour non trouvée'], 404);
        }
    }
}
