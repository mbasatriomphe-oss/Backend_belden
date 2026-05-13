<?php

namespace App\Http\Controllers;

use App\Models\clients;

class ClientController extends ApiCrudController
{
    protected string $modelClass = clients::class;

    protected function storeRules(): array
    {
        return [
            'nom' => 'required|string|max:90',
            'post_nom' => 'required|string|max:90',
            'prenom' => 'required|string|max:90',
            'adresse' => 'required|string|max:63',
            'ville' => 'required|string|max:50',
            'pays' => 'required|string|max:50',
            'contact' => 'required|string|max:50',
            'iduser' => 'nullable|integer|exists:users,id',
        ];
    }

    protected function updateRules(\Illuminate\Database\Eloquent\Model $model): array
    {
        return [
            'nom' => 'sometimes|string|max:90',
            'post_nom' => 'sometimes|string|max:90',
            'prenom' => 'sometimes|string|max:90',
            'adresse' => 'sometimes|string|max:63',
            'ville' => 'sometimes|string|max:50',
            'pays' => 'sometimes|string|max:50',
            'contact' => 'sometimes|string|max:50',
            'iduser' => 'nullable|integer|exists:users,id',
        ];
    }
}