<?php

namespace App\Http\Controllers;

use App\Models\vendeurs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class VendeurController extends ApiCrudController
{
    protected string $modelClass = vendeurs::class;

    protected function storeRules(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:vendeurs,code',
            'email' => 'required|email|max:255|unique:vendeurs,email',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('vendeurs', 'code')->ignore($model->getKey())],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('vendeurs', 'email')->ignore($model->getKey())],
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ];
    }
}