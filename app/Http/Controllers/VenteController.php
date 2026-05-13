<?php

namespace App\Http\Controllers;

use App\Models\ventes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VenteController extends ApiCrudController
{
    protected string $modelClass = ventes::class;

    protected function storeRules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:ventes,code',
            'date' => 'required|date',
            'id_vendeur' => 'required|integer|exists:vendeurs,id',
            'id_client' => 'required|integer|exists:clients,id',
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('ventes', 'code')->ignore($model->getKey())],
            'date' => 'sometimes|date',
            'id_vendeur' => 'sometimes|integer|exists:vendeurs,id',
            'id_client' => 'sometimes|integer|exists:clients,id',
        ];
    }

    protected function prepareStoreData(array $validated, Request $request): array
    {
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode('ventes', 'code', 'VEN');
        }

        return $validated;
    }
}