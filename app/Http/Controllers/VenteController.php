<?php

namespace App\Http\Controllers;

use App\Models\ventes;
use App\Models\ligne_ventes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VenteController extends ApiCrudController
{
    protected string $modelClass = ventes::class;
    protected array $searchable = ['code'];

    protected function indexQuery(Request $request): Builder
    {
        $query = ventes::with(['vendeur', 'client', 'lignes.produit', 'lignes.devise']);

        if ($request->filled('id_vendeur')) {
            $query->where('id_vendeur', $request->integer('id_vendeur'));
        }

        if ($request->filled('id_client')) {
            $query->where('id_client', $request->integer('id_client'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date('date'));
        }

        return $query;
    }

    protected function storeRules(): array
    {
        return [
            'code'                    => 'sometimes|string|max:50|unique:ventes,code',
            'date'                    => 'required|date',
            'id_vendeur'              => 'required|integer|exists:vendeurs,id',
            'id_client'               => 'required|integer|exists:clients,id',
            'lignes'                  => 'required|array|min:1',
            'lignes.*.id_produit'     => 'required|integer|exists:produits,id',
            'lignes.*.quantite'       => 'required|integer|min:1',
            'lignes.*.prix_vente'     => 'required|numeric|min:0',
            'lignes.*.id_devise'      => 'required|integer|exists:devises,id',
        ];
    }

    protected function updateRules(Model $model): array
    {
        return [
            'code'       => ['sometimes', 'string', 'max:50', Rule::unique('ventes', 'code')->ignore($model->getKey())],
            'date'       => 'sometimes|date',
            'id_vendeur' => 'sometimes|integer|exists:vendeurs,id',
            'id_client'  => 'sometimes|integer|exists:clients,id',
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeRules());

        $lineItems   = $validated['lignes'];
        $productIds  = array_map(static fn (array $l) => (int) $l['id_produit'], $lineItems);

        if (count($productIds) !== count(array_unique($productIds))) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Un même produit ne peut apparaître qu\'une seule fois dans la même vente.',
            ], 422);
        }

        $created = DB::transaction(function () use ($validated, $lineItems) {
            $code = $validated['code'] ?? $this->generateUniqueCode('ventes', 'code', 'VEN');

            /** @var ventes $vente */
            $vente = ventes::create([
                'code'       => $code,
                'date'       => $validated['date'],
                'id_vendeur' => (int) $validated['id_vendeur'],
                'id_client'  => (int) $validated['id_client'],
            ]);

            foreach ($lineItems as $item) {
                ligne_ventes::create([
                    'id_vente'   => $vente->id,
                    'id_produit' => (int) $item['id_produit'],
                    'quantite'   => (int) $item['quantite'],
                    'prix_vente' => $item['prix_vente'],
                    'id_devise'  => (int) $item['id_devise'],
                ]);
            }

            return $vente->fresh(['client', 'vendeur', 'lignes.produit', 'lignes.devise']);
        });

        return response()->json([
            'status' => 'success',
            'data'   => $created,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $vente     = ventes::findOrFail($id);
        $validated = $request->validate($this->updateRules($vente));
        $vente->update($validated);

        return response()->json([
            'status' => 'success',
            'data'   => $vente->fresh(['client', 'vendeur', 'lignes.produit', 'lignes.devise']),
        ]);
    }
}