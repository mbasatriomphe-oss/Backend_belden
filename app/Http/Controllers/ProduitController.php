<?php

namespace App\Http\Controllers;

use App\Models\produits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProduitController extends ApiCrudController
{
    protected string $modelClass = produits::class;
    protected array $searchable = ['code', 'nom', 'description'];

    protected function indexQuery(Request $request): Builder
    {
        $query = produits::with([
            'unite',
            'categorie',
            'variantes' => function ($relation) {
                $relation->selectRaw('variantes_produits.*, COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN m.type_mouvement IN ("entree", "retour") THEN m.quantite
                                WHEN m.type_mouvement = "sortie" THEN -m.quantite
                                ELSE 0
                            END
                        )
                        FROM lots l
                        LEFT JOIN mouvements_stock_fifos m ON m.id_lot = l.id
                        WHERE l.id_variante_produit = variantes_produits.id
                    ), 0) as quantite_stock');
            },
        ]);

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->integer('categorie_id'));
        }

        if ($request->filled('unite_id')) {
            $query->where('unite_id', $request->integer('unite_id'));
        }

        return $query;
    }

    protected function storeRules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:produits,code',
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
            'photo' => 'nullable|string|max:255',
            'photo_file' => 'nullable|file|image|max:4096',
            'unite_id' => 'nullable|integer|exists:unites,id',
            'categorie_id' => 'required|integer|exists:categories,id',
            'has_variantes' => ['required', 'boolean'],
            'prix_achat' => 'nullable|numeric|min:0',
            'prix_vente' => 'nullable|numeric|min:0',
            'quantite_stock' => 'nullable|integer|min:0',
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('produits', 'code')->ignore($model->getKey())],
            'nom' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'photo' => 'nullable|string|max:255',
            'photo_file' => 'nullable|file|image|max:4096',
            'unite_id' => 'nullable|integer|exists:unites,id',
            'categorie_id' => 'sometimes|integer|exists:categories,id',
            'has_variantes' => ['sometimes', 'boolean'],
            'prix_achat' => 'nullable|numeric|min:0',
            'prix_vente' => 'nullable|numeric|min:0',
            'quantite_stock' => 'nullable|integer|min:0',
        ];
    }

    protected function prepareStoreData(array $validated, Request $request): array
    {
        if (array_key_exists('has_variantes', $validated)) {
            $validated['has_variantes'] = filter_var($validated['has_variantes'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($validated['has_variantes'] === null) {
                $validated['has_variantes'] = false;
            }
        }

        if (($validated['has_variantes'] ?? false) === true) {
            $validated['unite_id'] = null;
            $validated['prix_achat'] = null;
            $validated['prix_vente'] = null;
            $validated['quantite_stock'] = null;
        }

        $validated['photo'] = $this->storePhoto($request, $validated['photo'] ?? null);

        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode('produits', 'code', 'PRO');
        }

        unset($validated['photo_file']);

        return $validated;
    }

    protected function prepareUpdateData(array $validated, Model $model, Request $request): array
    {
        if (array_key_exists('has_variantes', $validated)) {
            $validated['has_variantes'] = filter_var($validated['has_variantes'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($validated['has_variantes'] === null) {
                $validated['has_variantes'] = false;
            }
        }

        if (($validated['has_variantes'] ?? $model->has_variantes) === true) {
            $validated['unite_id'] = null;
            $validated['prix_achat'] = null;
            $validated['prix_vente'] = null;
            $validated['quantite_stock'] = null;
        }

        $validated['photo'] = $this->storePhoto($request, $validated['photo'] ?? $model->getAttribute('photo'));
        unset($validated['photo_file']);

        return $validated;
    }

    private function storePhoto(Request $request, ?string $currentValue): ?string
    {
        $photoFile = $request->file('photo_file');

        if ($photoFile instanceof UploadedFile) {
            return $photoFile->store('produits', 'public');
        }

        return $currentValue !== null ? trim($currentValue) : null;
    }
}