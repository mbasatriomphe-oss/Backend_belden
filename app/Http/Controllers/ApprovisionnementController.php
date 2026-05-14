<?php

namespace App\Http\Controllers;

use App\Models\approvisionnements;
use App\Models\ligne_approvisionnements;
use App\Models\lots;
use App\Models\mouvements_stock_fifos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ApprovisionnementController extends ApiCrudController
{
    protected string $modelClass = approvisionnements::class;
    protected array $searchable = ['code'];

    protected function indexQuery(Request $request): Builder
    {
        $query = approvisionnements::with(['fournisseur', 'user', 'lignes.lots']);

        if ($request->filled('id_fournisseur')) {
            $query->where('id_fournisseur', $request->integer('id_fournisseur'));
        }

        if ($request->filled('id_user')) {
            $query->where('id_user', $request->integer('id_user'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date('date'));
        }

        return $query;
    }

    protected function storeRules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:approvisionnements,code',
            'date' => 'required|date',
            'id_user' => 'required|integer|exists:users,id',
            'id_fournisseur' => 'required|integer|exists:fournisseurs,id',
            'lignes' => 'required|array|min:1',
            'lignes.*.id_produit' => 'required|integer|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
            'lignes.*.prix_vente' => 'nullable|numeric|min:0',
            'lignes.*.id_devise' => 'required|integer|exists:devises,id',
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

    public function store(Request $request): 
    JsonResponse
    {
        $rules = $this->storeRules();
        $validated = $request->validate($rules);

        if ($validated['date'] !== now()->toDateString()) {
            return response()->json([
                'status' => 'error',
                'message' => 'La date d\'un approvisionnement doit être celle du jour.',
            ], 422);
        }

        $lineItems = $validated['lignes'];
        $productIds = array_map(static fn (array $line) => (int) $line['id_produit'], $lineItems);

        if (count($productIds) !== count(array_unique($productIds))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chaque produit ne peut apparaître qu\'une seule fois dans le même approvisionnement.',
            ], 422);
        }

        $payload = [
            'code' => $validated['code'] ?? null,
            'date' => $validated['date'],
            'id_user' => $validated['id_user'],
            'id_fournisseur' => $validated['id_fournisseur'],
        ];

        $created = DB::transaction(function () use ($payload, $lineItems) {
            if (empty($payload['code'])) {
                $payload['code'] = $this->generateUniqueCode('approvisionnements', 'code', 'APP');
            }

            /** @var approvisionnements $approvisionnement */
            $approvisionnement = approvisionnements::create($payload);

            $driver = DB::connection()->getDriverName();
            $createdLines = [];

            foreach ($lineItems as $lineItem) {
                $line = ligne_approvisionnements::create([
                    'id_approvisionnement' => $approvisionnement->id,
                    'id_produit' => (int) $lineItem['id_produit'],
                    'quantite' => (int) $lineItem['quantite'],
                    'prix_unitaire' => $lineItem['prix_unitaire'],
                    'prix_vente' => $lineItem['prix_vente'] ?? null,
                    'id_devise' => (int) $lineItem['id_devise'],
                ]);

                if ($driver === 'sqlite') {
                    $lot = lots::create([
                        'numero_lot' => $this->generateLotNumber($approvisionnement->date, (int) $lineItem['id_produit']),
                        'id_produit' => (int) $lineItem['id_produit'],
                        'id_approvisionnement' => $approvisionnement->id,
                        'id_ligne_approvisionnement' => $line->id,
                        'quantite_initial' => (int) $lineItem['quantite'],
                        'date_reception' => $approvisionnement->date,
                        'date_expiration' => null,
                        'id_devise' => (int) $lineItem['id_devise'],
                    ]);

                    mouvements_stock_fifos::create([
                        'id_lot' => $lot->id,
                        'type_mouvement' => 'entree',
                        'quantite' => (int) $lineItem['quantite'],
                        'quantite_restante_avant' => 0,
                        'quantite_restante_apres' => (int) $lineItem['quantite'],
                        'date_mouvement' => $approvisionnement->date,
                    ]);
                }

                $createdLines[] = $line->fresh(['produit', 'devise', 'lots']);
            }

            return $approvisionnement->fresh(['fournisseur', 'user', 'lignes.produit', 'lignes.devise', 'lignes.lots']);
        });

        return response()->json([
            'status' => 'success',
            'data' => $created,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $approvisionnement = approvisionnements::findOrFail($id);
        $validated = $request->validate($this->updateRules($approvisionnement));

        if (array_key_exists('date', $validated) && $validated['date'] !== now()->toDateString()) {
            return response()->json([
                'status' => 'error',
                'message' => 'La date d\'un approvisionnement doit être celle du jour.',
            ], 422);
        }

        $approvisionnement->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $approvisionnement->fresh(['fournisseur', 'user', 'lignes.produit', 'lignes.devise', 'lignes.lots']),
        ]);
    }

    private function generateLotNumber(string $date, int $productId): string
    {
        do {
            $number = sprintf('LOT-%s-%05d-%04d', str_replace('-', '', $date), $productId, random_int(0, 9999));
        } while (lots::query()->where('numero_lot', $number)->exists());

        return $number;
    }
}