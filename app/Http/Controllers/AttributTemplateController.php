<?php

namespace App\Http\Controllers;

use App\Models\AttributTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AttributTemplateController extends ApiCrudController
{
    protected string $modelClass = AttributTemplate::class;
    protected array $searchable = ['id'];

    protected function indexQuery(Request $request): Builder
    {
        $query = AttributTemplate::with(['categorie', 'attribut']);

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->integer('categorie_id'));
        }

        if ($request->filled('attribut_id')) {
            $query->where('attribut_id', $request->integer('attribut_id'));
        }

        return $query->orderBy('ordre_affichage');
    }

    protected function storeRules(): array
    {
        return [
            'categorie_id' => 'required|integer|exists:categories,id',
            'attribut_id' => 'required|integer|exists:attributs,id',
            'ordre_affichage' => 'nullable|integer|min:0',
            'obligatoire' => 'nullable|boolean',
            'est_visuel' => 'nullable|boolean',
        ];
    }

    protected function updateRules(\Illuminate\Database\Eloquent\Model $model): array
    {
        return [
            'categorie_id' => 'sometimes|integer|exists:categories,id',
            'attribut_id' => 'sometimes|integer|exists:attributs,id',
            'ordre_affichage' => 'nullable|integer|min:0',
            'obligatoire' => 'nullable|boolean',
            'est_visuel' => 'nullable|boolean',
        ];
    }
}
