<?php

namespace App\Http\Controllers;

use App\Models\caisse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaisseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $query = caisse::query();

        if ($search) {
            $query->where('solde', 'like', "%{$search}%");
        }

        $items = $query->orderBy('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des caisses rÃ©cupÃ©rÃ©e avec succÃ¨s',
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function all(): JsonResponse
    {
        $items = caisse::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste complÃ¨te des caisses rÃ©cupÃ©rÃ©e',
            'data' => $items,
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $item = caisse::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Caisse rÃ©cupÃ©rÃ©e avec succÃ¨s',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Caisse non trouvÃ©e'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_devise' => 'required|integer|exists:devises,id',
            'solde' => 'required|numeric',
        ]);

        $item = caisse::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Caisse crÃ©Ã©e avec succÃ¨s',
            'data' => $item,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = caisse::findOrFail($id);

            $validated = $request->validate([
                'id_devise' => 'required|integer|exists:devises,id',
                'solde' => 'required|numeric',
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Caisse mise Ã  jour avec succÃ¨s',
                'data' => $item,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Caisse non trouvÃ©e'], 404);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $item = caisse::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Caisse supprimÃ©e avec succÃ¨s',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Caisse non trouvÃ©e'], 404);
        }
    }
}
