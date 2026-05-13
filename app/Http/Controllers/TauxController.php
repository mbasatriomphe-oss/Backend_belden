<?php

namespace App\Http\Controllers;

use App\Models\Taux;

class TauxController extends ApiCrudController
{
    protected string $modelClass = Taux::class;

    protected function storeRules(): array
    {
        return [
            'devise_source' => 'required|integer|exists:devises,id',
            'devise_but' => 'required|integer|exists:devises,id|different:devise_source',
            'valeur' => 'required|numeric|min:0',
            'date_effet' => 'required|date',
            'statut' => 'sometimes|in:actif,inactif',
        ];
    }

    protected function updateRules(\Illuminate\Database\Eloquent\Model $model): array
    {
        return [
            'devise_source' => 'sometimes|integer|exists:devises,id',
            'devise_but' => 'sometimes|integer|exists:devises,id|different:devise_source',
            'valeur' => 'sometimes|numeric|min:0',
            'date_effet' => 'sometimes|date',
            'statut' => 'sometimes|in:actif,inactif',
        ];
    }

    protected function indexQuery(Request $request): Builder
    {
        $query = Taux::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('devise_source')) {
            $query->where('devise_source', $request->integer('devise_source'));
        }

        if ($request->filled('devise_but')) {
            $query->where('devise_but', $request->integer('devise_but'));
        }

        if ($request->filled('date_effet')) {
            $query->whereDate('date_effet', $request->date('date_effet'));
        }

        return $query->orderByDesc('date_effet')->orderByDesc('id');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeRules());
        $validated['statut'] = $validated['statut'] ?? 'inactif';

        $alreadyExists = Taux::query()
            ->where('devise_source', $validated['devise_source'])
            ->where('devise_but', $validated['devise_but'])
            ->whereDate('date_effet', $validated['date_effet'])
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Un taux existe déjà pour ces devises à cette date.',
            ], 422);
        }

        $taux = Taux::create($validated);

        if ($validated['statut'] === 'actif') {
            $this->deactivateSiblingRates($taux);
        }

        return response()->json([
            'status' => 'success',
            'data' => $taux,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $taux = Taux::findOrFail($id);
        $validated = $request->validate($this->updateRules($taux));

        $nextSource = $validated['devise_source'] ?? $taux->devise_source;
        $nextBut = $validated['devise_but'] ?? $taux->devise_but;
        $nextDate = $validated['date_effet'] ?? $taux->date_effet;

        $alreadyExists = Taux::query()
            ->where('id', '!=', $taux->getKey())
            ->where('devise_source', $nextSource)
            ->where('devise_but', $nextBut)
            ->whereDate('date_effet', $nextDate)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Un taux existe déjà pour ces devises à cette date.',
            ], 422);
        }

        $taux->update($validated);

        if (($validated['statut'] ?? $taux->statut) === 'actif') {
            $this->deactivateSiblingRates($taux->fresh());
        }

        return response()->json([
            'status' => 'success',
            'data' => $taux->fresh(),
        ]);
    }

    public function actifByDate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'devise_source' => 'nullable|integer|exists:devises,id',
            'devise_but' => 'nullable|integer|exists:devises,id',
        ]);

        $query = Taux::query()->where('statut', 'actif');

        if (! empty($validated['date'])) {
            $query->whereDate('date_effet', '<=', $validated['date']);
        }

        if (! empty($validated['devise_source'])) {
            $query->where('devise_source', $validated['devise_source']);
        }

        if (! empty($validated['devise_but'])) {
            $query->where('devise_but', $validated['devise_but']);
        }

        $query->orderByDesc('date_effet')->orderByDesc('id');

        if (! empty($validated['devise_source']) && ! empty($validated['devise_but'])) {
            $taux = $query->first();

            if (! $taux) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Aucun taux actif trouvé pour ce couple de devises à cette date.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $taux,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    private function deactivateSiblingRates(Taux $taux): void
    {
        Taux::query()
            ->where('id', '!=', $taux->getKey())
            ->where('devise_source', $taux->devise_source)
            ->where('devise_but', $taux->devise_but)
            ->update(['statut' => 'inactif']);
    }
}