<?php

namespace App\Http\Controllers;

use App\Models\produits;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProduitController extends ApiCrudController
{
    protected string $modelClass = produits::class;

    protected function storeRules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:produits,code',
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
            'photo' => 'nullable|string|max:255',
            'unite_id' => 'required|integer|exists:unites,id',
            'categorie_id' => 'required|integer|exists:categories,id',
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('produits', 'code')->ignore($model->getKey())],
            'nom' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'photo' => 'nullable|string|max:255',
            'unite_id' => 'sometimes|integer|exists:unites,id',
            'categorie_id' => 'sometimes|integer|exists:categories,id',
        ];
    }

    protected function prepareStoreData(array $validated, Request $request): array
    {
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode('produits', 'code', 'PRO');
        }

        return $validated;
    }
}