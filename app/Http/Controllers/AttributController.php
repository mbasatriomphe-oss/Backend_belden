<?php

namespace App\Http\Controllers;

use App\Models\Attribut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AttributController extends ApiCrudController
{
    protected string $modelClass = Attribut::class;
    protected array $searchable = ['nom'];

    protected function indexQuery(Request $request): Builder
    {
        return Attribut::query()->orderBy('nom');
    }

    protected function storeRules(): array
    {
        return [
            'nom' => 'required|string|max:50|unique:attributs,nom',
            'type_affichage' => 'nullable|string|max:20',
        ];
    }

    protected function updateRules(\Illuminate\Database\Eloquent\Model $model): array
    {
        return [
            'nom' => ['sometimes', 'string', 'max:50', \Illuminate\Validation\Rule::unique('attributs', 'nom')->ignore($model->getKey())],
            'type_affichage' => 'nullable|string|max:20',
        ];
    }
}
