<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RapportController extends Controller
{
    public function recapJournalier(): JsonResponse
    {
        $recap = DB::table('v_recap_journalier')->first();

        return response()->json([
            'status' => 'success',
            'data' => $recap,
        ]);
    }

    public function etatCaisses(): JsonResponse
    {
        $caisses = DB::table('v_etat_caisses')->orderBy('devise_code')->get();

        return response()->json([
            'status' => 'success',
            'data' => $caisses,
        ]);
    }

    public function chiffreAffaires(Request $request): JsonResponse
    {
        $query = DB::table('v_chiffre_affaires');

        if ($request->filled('date_debut')) {
            $query->whereDate('date_vente', '>=', $request->date('date_debut'));
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_vente', '<=', $request->date('date_fin'));
        }

        if ($request->filled('devise_code')) {
            $query->where('devise_code', $request->string('devise_code'));
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderByDesc('date_vente')->get(),
        ]);
    }

    public function topProduits(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('limit', 10), 100));

        $produits = DB::table('v_top_produits')
            ->orderByDesc('chiffre_affaires')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $produits,
        ]);
    }

    public function lotsExpiration(Request $request): JsonResponse
    {
        $query = DB::table('v_lots_expiration');

        if ($request->filled('statut_expiration')) {
            $query->where('statut_expiration', $request->string('statut_expiration'));
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('date_expiration')->get(),
        ]);
    }

    public function margeProduit(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->query('limit', 50), 100));

        return response()->json([
            'status' => 'success',
            'data' => DB::table('v_marge_produit')
                ->orderByDesc('marge_pourcentage')
                ->limit($limit)
                ->get(),
        ]);
    }

    public function mouvementsCaisse(Request $request): JsonResponse
    {
        $query = DB::table('v_mouvements_caisse');

        if ($request->filled('devise_code')) {
            $query->where('devise_code', $request->string('devise_code'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderByDesc('date_mouvement')->get(),
        ]);
    }
}
