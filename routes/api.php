<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UniteController;
use App\Http\Controllers\DeviseController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\VendeurController;
use App\Http\Controllers\ApprovisionnementController;
use App\Http\Controllers\LigneApprovisionnementController;
use App\Http\Controllers\VenteController;
use App\Http\Controllers\LigneVenteController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\RetourController;
use App\Http\Controllers\LigneRetourController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\MouvementStockFifoController;
use App\Http\Controllers\TransactionCaisseController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\TauxController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==================== ROUTES PUBLIQUES (sans authentification) ====================

// Inscription et connexion
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-vendeur', [AuthController::class, 'loginVendeur']);

// Route publique pour créer le premier admin (à désactiver après utilisation)
Route::post('/register-admin', [AuthController::class, 'registerAdmin']);

// ==================== ROUTES PROTÉGÉES PAR AUTHENTIFICATION ====================

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Déconnexion (nécessite d'être connecté)
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Récupérer l'utilisateur connecté
    Route::get('/user', function(Request $request) {
        return $request->user();
    });

    Route::middleware(['admin.or.vendeur'])->group(function () {
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('ventes', VenteController::class);
        Route::apiResource('retours', RetourController::class);

        Route::get('/produits', [ProduitController::class, 'index'])->name('produits.index');
        Route::get('/produits/{id}', [ProduitController::class, 'show'])->name('produits.show');
        Route::get('/devises', [DeviseController::class, 'index'])->name('devises.index');
        Route::get('/devises/{id}', [DeviseController::class, 'show'])->name('devises.show');
        Route::get('/vendeurs', [VendeurController::class, 'index'])->name('vendeurs.index');
        Route::get('/vendeurs/{id}', [VendeurController::class, 'show'])->name('vendeurs.show');
        Route::get('/lots', [LotController::class, 'index'])->name('lots.index');
        Route::get('/lots/{id}', [LotController::class, 'show'])->name('lots.show');
    });
    
    // Routes unités (lecture pour tous les utilisateurs authentifiés)
    Route::prefix('unites')->group(function () {
        Route::get('/', [UniteController::class, 'index'])->name('unites.index');
        Route::get('/all', [UniteController::class, 'all'])->name('unites.all');
        Route::get('/{id}', [UniteController::class, 'show'])->name('unites.show');
    });
    
    // ==================== ROUTES ADMIN UNIQUEMENT ====================
    
    Route::middleware(['admin'])->group(function () {
        // Gestion complète des utilisateurs (CRUD)
        Route::apiResource('users', UserController::class);
        Route::apiResource('devises', DeviseController::class);
        Route::apiResource('categories', CategorieController::class);
        Route::apiResource('produits', ProduitController::class);
        Route::apiResource('fournisseurs', FournisseurController::class);
        Route::apiResource('approvisionnements', ApprovisionnementController::class);
        Route::apiResource('ligne-approvisionnements', LigneApprovisionnementController::class);
        Route::apiResource('ligne-ventes', LigneVenteController::class);
        Route::apiResource('ligne-retours', LigneRetourController::class);
        Route::apiResource('caisses', CaisseController::class);
        Route::get('/caisses/devise/{idDevise}', [CaisseController::class, 'byDevise'])->name('caisses.byDevise');
        Route::post('/caisses/{id}/credit', [CaisseController::class, 'credit'])->name('caisses.credit');
        Route::post('/caisses/{id}/debit', [CaisseController::class, 'debit'])->name('caisses.debit');
        Route::apiResource('transactions-caisses', TransactionCaisseController::class);
        Route::apiResource('mouvements-stock-fifos', MouvementStockFifoController::class);
        Route::get('/stocks/disponible', [MouvementStockFifoController::class, 'stocksDisponibles'])->name('stocks.disponible');
        Route::get('/stocks/produit/{produitId}', [MouvementStockFifoController::class, 'stockParProduit'])->name('stocks.produit');
        Route::get('/stocks/lot/{lotId}', [MouvementStockFifoController::class, 'stockParLot'])->name('stocks.lot');
        Route::get('/mouvements-stock-fifos/produit/{produitId}', [MouvementStockFifoController::class, 'mouvementsParProduit'])->name('mouvements-stock-fifos.produit');
        Route::get('/mouvements-stock-fifos/lot/{lotId}', [MouvementStockFifoController::class, 'mouvementsParLot'])->name('mouvements-stock-fifos.lot');
        Route::get('/rapports/recap-journalier', [RapportController::class, 'recapJournalier'])->name('rapports.recap-journalier');
        Route::get('/rapports/etat-caisses', [RapportController::class, 'etatCaisses'])->name('rapports.etat-caisses');
        Route::get('/rapports/chiffre-affaires', [RapportController::class, 'chiffreAffaires'])->name('rapports.chiffre-affaires');
        Route::get('/rapports/top-produits', [RapportController::class, 'topProduits'])->name('rapports.top-produits');
        Route::get('/rapports/lots-expiration', [RapportController::class, 'lotsExpiration'])->name('rapports.lots-expiration');
        Route::get('/rapports/marge-produit', [RapportController::class, 'margeProduit'])->name('rapports.marge-produit');
        Route::get('/rapports/mouvements-caisse', [RapportController::class, 'mouvementsCaisse'])->name('rapports.mouvements-caisse');
        Route::apiResource('taux', TauxController::class);
        Route::get('/taux/actif', [TauxController::class, 'actifByDate'])->name('taux.actif');
        
        // Routes d'écriture pour les unités (CRUD)
        Route::prefix('unites')->group(function () {
            Route::post('/', [UniteController::class, 'store'])->name('unites.store');
            Route::put('/{id}', [UniteController::class, 'update'])->name('unites.update');
            Route::delete('/{id}', [UniteController::class, 'destroy'])->name('unites.destroy');
        });
    });
});