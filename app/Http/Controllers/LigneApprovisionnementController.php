<?php

namespace App\Http\Controllers;

use App\Models\ligne_approvisionnements;

class LigneApprovisionnementController extends ApiCrudController
{
    protected string $modelClass = ligne_approvisionnements::class;

    protected function storeRules(): array
    {
        return [
            'id_approvisionnement' => 'required|integer|exists:approvisionnements,id',
            'id_produit' => 'required|integer|exists:produits,id',
            'quantite' => 'required|integer|min:1',
            'prix_unitaire' => 'required|numeric|min:0',
            'prix_vente' => 'nullable|numeric|min:0',
            'id_devise' => 'required|integer|exists:devises,id',
        ];
    }

    protected function updateRules(\Illuminate\Database\Eloquent\Model $model): array
    {
        return [
            'id_approvisionnement' => 'sometimes|integer|exists:approvisionnements,id',
            'id_produit' => 'sometimes|integer|exists:produits,id',
            'quantite' => 'sometimes|integer|min:1',
            'prix_unitaire' => 'sometimes|numeric|min:0',
            'prix_vente' => 'nullable|numeric|min:0',
            'id_devise' => 'sometimes|integer|exists:devises,id',
        ];
    }
}