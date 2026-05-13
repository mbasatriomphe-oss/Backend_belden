<?php

namespace App\Http\Controllers;

use App\Models\retours;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RetourController extends ApiCrudController
{
    protected string $modelClass = retours::class;

    protected function storeRules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:retours,code',
            'date_retour' => 'required|date',
            'id_vente' => 'required|integer|exists:ventes,id',
            'id_client' => 'required|integer|exists:clients,id',
            'id_vendeur' => 'required|integer|exists:vendeurs,id',
            'motif' => 'nullable|string',
            'commentaire' => 'nullable|string',
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('retours', 'code')->ignore($model->getKey())],
            'date_retour' => 'sometimes|date',
            'id_vente' => 'sometimes|integer|exists:ventes,id',
            'id_client' => 'sometimes|integer|exists:clients,id',
            'id_vendeur' => 'sometimes|integer|exists:vendeurs,id',
            'motif' => 'nullable|string',
            'commentaire' => 'nullable|string',
        ];
    }

    protected function prepareStoreData(array $validated, Request $request): array
    {
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode('retours', 'code', 'RET');
        }

        return $validated;
    }
}