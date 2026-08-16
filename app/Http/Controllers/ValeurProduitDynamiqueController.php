<?php

namespace App\Http\Controllers;

use App\Models\ValeurProduitDynamique;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ValeurProduitDynamiqueController extends ApiCrudController
{
    protected string $modelClass = ValeurProduitDynamique::class;
    protected array $searchable = ['valeur'];

    protected function indexQuery(Request $request): Builder
    {
        $query = ValeurProduitDynamique::with(['produit', 'attributTemplate.attribut']);

        if ($request->filled('produit_id')) {
            $query->where('produit_id', $request->integer('produit_id'));
        }

        if ($request->filled('attribut_template_id')) {
            $query->where('attribut_template_id', $request->integer('attribut_template_id'));
        }

        return $query;
    }

    protected function storeRules(): array
    {
        return [
            'produit_id' => 'required|integer|exists:produits,id',
            'attribut_template_id' => 'required|integer|exists:attributs_templates,id',
            'valeur' => 'required|string|max:255',
        ];
    }

    protected function updateRules(\Illuminate\Database\Eloquent\Model $model): array
    {
        return [
            'produit_id' => 'sometimes|integer|exists:produits,id',
            'attribut_template_id' => 'sometimes|integer|exists:attributs_templates,id',
            'valeur' => 'sometimes|string|max:255',
        ];
    }
}
