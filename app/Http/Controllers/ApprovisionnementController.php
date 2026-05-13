<?php

namespace App\Http\Controllers;

use App\Models\approvisionnements;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApprovisionnementController extends ApiCrudController
{
    protected string $modelClass = approvisionnements::class;

    protected function storeRules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:approvisionnements,code',
            'date' => 'required|date',
            'id_user' => 'required|integer|exists:users,id',
            'id_fournisseur' => 'required|integer|exists:fournisseurs,id',
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('approvisionnements', 'code')->ignore($model->getKey())],
            'date' => 'sometimes|date',
            'id_user' => 'sometimes|integer|exists:users,id',
            'id_fournisseur' => 'sometimes|integer|exists:fournisseurs,id',
        ];
    }

    protected function prepareStoreData(array $validated, Request $request): array
    {
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode('approvisionnements', 'code', 'APP');
        }

        return $validated;
    }
}