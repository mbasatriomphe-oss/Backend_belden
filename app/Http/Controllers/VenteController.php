<?php

namespace App\Http\Controllers;

use App\Models\ventes;
use App\Models\ligne_ventes;
use App\Models\User;
use App\Notifications\CrudActionNotification;
use App\Notifications\StockInsuffisantNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class VenteController extends ApiCrudController
{
    protected string $modelClass = ventes::class;
    protected array $searchable = ['code'];

    private function ensureVendorUser(): ?JsonResponse
    {
        $user = auth()->user();
        $role = $user->role ?? null;

        if ($role !== 'vendeur') {
            return response()->json([
                'status' => 'error',
                'message' => 'Seul un vendeur peut créer ou modifier une vente.',
            ], 403);
        }

        return null;
    }

    private function notifySaleAction(string $action, ventes $vente): void
    {
        $actor = auth()->user();
        $recipients = collect(User::query()->where('role', 'admin')->get());

        if ($actor && method_exists($actor, 'notify')) {
            $recipients->push($actor);
        }

        $recipients = $recipients->filter()->unique(function ($recipient): string {
            return get_class($recipient) . ':' . (string) $recipient->getKey();
        })->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $titles = [
            'created' => 'Vente réussie',
            'updated' => 'Vente modifiée',
            'deleted' => 'Vente supprimée',
        ];

        $messages = [
            'created' => 'La vente a été enregistrée avec succès.',
            'updated' => 'La vente a été modifiée avec succès.',
            'deleted' => 'La vente a été supprimée avec succès.',
        ];

        Notification::send($recipients, new CrudActionNotification([
            'type' => 'vente_' . $action,
            'title' => $titles[$action] ?? 'Vente',
            'message' => $messages[$action] ?? 'Action réalisée sur une vente.',
            'action' => $action,
            'entity' => 'Vente',
            'entity_id' => $vente->getKey(),
            'entity_name' => $vente->code,
            'actor_id' => $actor?->getKey(),
            'actor_name' => $actor?->nom ?? $actor?->name ?? $actor?->email ?? 'Système',
        ]));
    }

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
        if ($response = $this->ensureVendorUser()) {
            return $response;
        }

        $validated = $request->validate($this->storeRules());

        $lineItems   = $validated['lignes'];
        $productIds  = array_map(static fn (array $l) => (int) $l['id_produit'], $lineItems);

        if (count($productIds) !== count(array_unique($productIds))) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Un même produit ne peut apparaître qu\'une seule fois dans la même vente.',
            ], 422);
        }

        $stockRows = DB::table('v_stock_disponible')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $insufficientItems = [];
        foreach ($lineItems as $item) {
            $productId = (int) $item['id_produit'];
            $availableStock = (int) ($stockRows->get($productId)?->stock_actuel ?? 0);
            $requestedQuantity = (int) $item['quantite'];

            if ($availableStock < $requestedQuantity) {
                $product = DB::table('produits')->where('id', $productId)->first();
                $insufficientItems[] = [
                    'id_produit' => $productId,
                    'produit' => $product?->nom ?? $product?->code ?? "Produit #{$productId}",
                    'demande' => $requestedQuantity,
                    'disponible' => $availableStock,
                ];
            }
        }

        if ($insufficientItems !== []) {
            $admins = User::query()->where('role', 'admin')->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new StockInsuffisantNotification([
                    'message' => 'Une vente a été refusée car le stock était insuffisant.',
                    'vente_code' => $validated['code'] ?? null,
                    'items' => $insufficientItems,
                    'created_by' => (int) auth()->id(),
                    'created_by_name' => auth()->user()?->nom ?? auth()->user()?->email ?? 'Système',
                ]));
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Stock insuffisant pour cette vente',
                'details' => $insufficientItems,
            ], 422);
        }

        try {
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

            $this->notifySaleAction('created', $created);
        } catch (QueryException|\RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'Stock insuffisant pour cette vente')) {
                $admins = User::query()->where('role', 'admin')->get();

                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new StockInsuffisantNotification([
                        'message' => 'Le trigger de stock a refusé une vente.',
                        'vente_code' => $validated['code'] ?? null,
                        'items' => array_map(static fn (array $item) => [
                            'id_produit' => (int) $item['id_produit'],
                            'demande' => (int) $item['quantite'],
                        ], $lineItems),
                        'created_by' => (int) auth()->id(),
                        'created_by_name' => auth()->user()?->nom ?? auth()->user()?->email ?? 'Système',
                    ]));
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $created,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($response = $this->ensureVendorUser()) {
            return $response;
        }

        $vente     = ventes::findOrFail($id);
        $validated = $request->validate($this->updateRules($vente));
        $vente->update($validated);

        $freshVente = $vente->fresh(['client', 'vendeur', 'lignes.produit', 'lignes.devise']);
        $this->notifySaleAction('updated', $freshVente);

        return response()->json([
            'status' => 'success',
            'data'   => $freshVente,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        if ($response = $this->ensureVendorUser()) {
            return $response;
        }

        $vente = ventes::findOrFail($id);
        $this->notifySaleAction('deleted', $vente);
        $vente->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Suppression effectuee',
        ]);
    }

}