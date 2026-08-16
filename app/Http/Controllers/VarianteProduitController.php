<?php

namespace App\Http\Controllers;

use App\Models\VarianteProduit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VarianteProduitController extends ApiCrudController
{
    protected string $modelClass = VarianteProduit::class;
    protected array $searchable = ['code_sku', 'combinaison'];

    protected function indexQuery(Request $request): Builder
    {
        $query = VarianteProduit::query()
            ->selectRaw('variantes_produits.*, COALESCE((
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
                ), 0) as quantite_stock')
            ->with(['produit']);

        if ($request->filled('produit_id')) {
            $query->where('produit_id', $request->integer('produit_id'));
        }

        return $query->orderByDesc('variantes_produits.id');
    }

    protected function storeRules(): array
    {
        return [
            'produit_id' => 'required|integer|exists:produits,id',
            'code_sku' => ['nullable', 'string', 'max:255', 'unique:variantes_produits,code_sku'],
            'combinaison' => 'nullable|array',
            'combinaison.*' => 'sometimes|string|max:255',
            'quantite_stock' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'seuil_alerte' => 'nullable|integer|min:0',
            'stock_alerte' => 'nullable|integer|min:0',
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'produit_id' => 'sometimes|integer|exists:produits,id',
            'code_sku' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('variantes_produits', 'code_sku')->ignore($model->getKey())],
            'combinaison' => 'nullable|array',
            'combinaison.*' => 'sometimes|string|max:255',
            'quantite_stock' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0',
            'seuil_alerte' => 'nullable|integer|min:0',
            'stock_alerte' => 'nullable|integer|min:0',
        ];
    }

    protected function prepareStoreData(array $validated, Request $request): array
    {
        if (empty($validated['code_sku'] ?? null) && !empty($validated['produit_id'])) {
            $validated['code_sku'] = 'VAR-' . uniqid();
        }

        if (isset($validated['stock']) && !array_key_exists('quantite_stock', $validated)) {
            $validated['quantite_stock'] = $validated['stock'];
        }

        if (isset($validated['stock_alerte']) && !array_key_exists('seuil_alerte', $validated)) {
            $validated['seuil_alerte'] = $validated['stock_alerte'];
        }

        if (!empty($validated['combinaison']) && is_array($validated['combinaison'])) {
            $combinaison = [];

            if (array_is_list($validated['combinaison'])) {
                foreach ($validated['combinaison'] as $entry) {
                    if (is_array($entry) && isset($entry['key'], $entry['value'])) {
                        $combinaison[(string) $entry['key']] = (string) $entry['value'];
                    }
                }
            } else {
                foreach ($validated['combinaison'] as $key => $value) {
                    $combinaison[(string) $key] = (string) $value;
                }
            }

            $validated['combinaison'] = $combinaison;
        }

        return $validated;
    }

    protected function prepareUpdateData(array $validated, Model $model, Request $request): array
    {
        if (isset($validated['stock']) && !array_key_exists('quantite_stock', $validated)) {
            $validated['quantite_stock'] = $validated['stock'];
        }

        if (isset($validated['stock_alerte']) && !array_key_exists('seuil_alerte', $validated)) {
            $validated['seuil_alerte'] = $validated['stock_alerte'];
        }

        if (!empty($validated['combinaison']) && is_array($validated['combinaison'])) {
            $combinaison = [];

            if (array_is_list($validated['combinaison'])) {
                foreach ($validated['combinaison'] as $entry) {
                    if (is_array($entry) && isset($entry['key'], $entry['value'])) {
                        $combinaison[(string) $entry['key']] = (string) $entry['value'];
                    }
                }
            } else {
                foreach ($validated['combinaison'] as $key => $value) {
                    $combinaison[(string) $key] = (string) $value;
                }
            }

            $validated['combinaison'] = $combinaison;
        }

        return $validated;
    }
}
