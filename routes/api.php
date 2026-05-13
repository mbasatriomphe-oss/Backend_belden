<?php

use App\Http\Controllers\ApprovisionnementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DeviseController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\LigneApprovisionnementController;
use App\Http\Controllers\LigneRetourController;
use App\Http\Controllers\LigneVenteController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\MouvementStockFifoController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\RetourController;
use App\Http\Controllers\TauxController;
use App\Http\Controllers\TransactionCaisseController;
use App\Http\Controllers\UniteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendeurController;
use App\Http\Controllers\VenteController;
use Illuminate\Support\Facades\Route;

// ==================== ROUTES PUBLIQUES ====================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/vendeur/register', [AuthController::class, 'registerVendeur']);
Route::post('/vendeur/login', [AuthController::class, 'loginVendeur']);
Route::get('/test-connection', function () {
    return response()->json([
        'success' => true,
        'message' => 'API fonctionne!',
        'timestamp' => now()
    ]);
});

// ==================== ROUTES AUTHENTIFIÉES ====================
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Unités
    Route::get('/unites', [UniteController::class, 'index']);
    Route::get('/unites/all', [UniteController::class, 'all']);
    Route::get('/unites/{id}', [UniteController::class, 'show']);
    Route::post('/unites', [UniteController::class, 'store'])->middleware('admin');
    Route::put('/unites/{id}', [UniteController::class, 'update'])->middleware('admin');
    Route::delete('/unites/{id}', [UniteController::class, 'destroy'])->middleware('admin');

    // Devises
    Route::get('/devises', [DeviseController::class, 'index']);
    Route::get('/devises/all', [DeviseController::class, 'all']);
    Route::get('/devises/{id}', [DeviseController::class, 'show']);
    Route::post('/devises', [DeviseController::class, 'store'])->middleware('admin');
    Route::put('/devises/{id}', [DeviseController::class, 'update'])->middleware('admin');
    Route::delete('/devises/{id}', [DeviseController::class, 'destroy'])->middleware('admin');

    // Categories
    Route::get('/categories', [CategorieController::class, 'index']);
    Route::get('/categories/all', [CategorieController::class, 'all']);
    Route::get('/categories/{id}', [CategorieController::class, 'show']);
    Route::post('/categories', [CategorieController::class, 'store'])->middleware('admin');
    Route::put('/categories/{id}', [CategorieController::class, 'update'])->middleware('admin');
    Route::delete('/categories/{id}', [CategorieController::class, 'destroy'])->middleware('admin');

    // Produits
    Route::get('/produits', [ProduitController::class, 'index']);
    Route::get('/produits/all', [ProduitController::class, 'all']);
    Route::get('/produits/{id}', [ProduitController::class, 'show']);
    Route::post('/produits', [ProduitController::class, 'store'])->middleware('admin');
    Route::put('/produits/{id}', [ProduitController::class, 'update'])->middleware('admin');
    Route::delete('/produits/{id}', [ProduitController::class, 'destroy'])->middleware('admin');

    // Fournisseurs
    Route::get('/fournisseurs', [FournisseurController::class, 'index']);
    Route::get('/fournisseurs/all', [FournisseurController::class, 'all']);
    Route::get('/fournisseurs/{id}', [FournisseurController::class, 'show']);
    Route::post('/fournisseurs', [FournisseurController::class, 'store'])->middleware('admin');
    Route::put('/fournisseurs/{id}', [FournisseurController::class, 'update'])->middleware('admin');
    Route::delete('/fournisseurs/{id}', [FournisseurController::class, 'destroy'])->middleware('admin');

    // Taux
    Route::get('/taux', [TauxController::class, 'index']);
    Route::get('/taux/all', [TauxController::class, 'all']);
    Route::get('/taux/{id}', [TauxController::class, 'show']);
    Route::post('/taux', [TauxController::class, 'store'])->middleware('admin');
    Route::put('/taux/{id}', [TauxController::class, 'update'])->middleware('admin');
    Route::delete('/taux/{id}', [TauxController::class, 'destroy'])->middleware('admin');

    // Clients
    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/all', [ClientController::class, 'all']);
    Route::get('/clients/{id}', [ClientController::class, 'show']);
    Route::post('/clients', [ClientController::class, 'store'])->middleware('admin');
    Route::put('/clients/{id}', [ClientController::class, 'update'])->middleware('admin');
    Route::delete('/clients/{id}', [ClientController::class, 'destroy'])->middleware('admin');

    // Vendeurs
    Route::get('/vendeurs', [VendeurController::class, 'index']);
    Route::get('/vendeurs/all', [VendeurController::class, 'all']);
    Route::get('/vendeurs/{id}', [VendeurController::class, 'show']);
    Route::post('/vendeurs', [VendeurController::class, 'store'])->middleware('admin');
    Route::put('/vendeurs/{id}', [VendeurController::class, 'update'])->middleware('admin');
    Route::delete('/vendeurs/{id}', [VendeurController::class, 'destroy'])->middleware('admin');

    // Approvisionnements
    Route::get('/approvisionnements', [ApprovisionnementController::class, 'index']);
    Route::get('/approvisionnements/all', [ApprovisionnementController::class, 'all']);
    Route::get('/approvisionnements/{id}', [ApprovisionnementController::class, 'show']);
    Route::post('/approvisionnements', [ApprovisionnementController::class, 'store'])->middleware('admin');
    Route::put('/approvisionnements/{id}', [ApprovisionnementController::class, 'update'])->middleware('admin');
    Route::delete('/approvisionnements/{id}', [ApprovisionnementController::class, 'destroy'])->middleware('admin');

    // Lignes d'approvisionnement
    Route::get('/ligne-approvisionnements', [LigneApprovisionnementController::class, 'index']);
    Route::get('/ligne-approvisionnements/all', [LigneApprovisionnementController::class, 'all']);
    Route::get('/ligne-approvisionnements/{id}', [LigneApprovisionnementController::class, 'show']);
    Route::post('/ligne-approvisionnements', [LigneApprovisionnementController::class, 'store'])->middleware('admin');
    Route::put('/ligne-approvisionnements/{id}', [LigneApprovisionnementController::class, 'update'])->middleware('admin');
    Route::delete('/ligne-approvisionnements/{id}', [LigneApprovisionnementController::class, 'destroy'])->middleware('admin');

    // Ventes
    Route::get('/ventes', [VenteController::class, 'index']);
    Route::get('/ventes/all', [VenteController::class, 'all']);
    Route::get('/ventes/{id}', [VenteController::class, 'show']);
    Route::post('/ventes', [VenteController::class, 'store'])->middleware('admin');
    Route::put('/ventes/{id}', [VenteController::class, 'update'])->middleware('admin');
    Route::delete('/ventes/{id}', [VenteController::class, 'destroy'])->middleware('admin');

    // Lignes de vente
    Route::get('/ligne-ventes', [LigneVenteController::class, 'index']);
    Route::get('/ligne-ventes/all', [LigneVenteController::class, 'all']);
    Route::get('/ligne-ventes/{id}', [LigneVenteController::class, 'show']);
    Route::post('/ligne-ventes', [LigneVenteController::class, 'store'])->middleware('admin');
    Route::put('/ligne-ventes/{id}', [LigneVenteController::class, 'update'])->middleware('admin');
    Route::delete('/ligne-ventes/{id}', [LigneVenteController::class, 'destroy'])->middleware('admin');

    // Lots
    Route::get('/lots', [LotController::class, 'index']);
    Route::get('/lots/all', [LotController::class, 'all']);
    Route::get('/lots/{id}', [LotController::class, 'show']);
    Route::post('/lots', [LotController::class, 'store'])->middleware('admin');
    Route::put('/lots/{id}', [LotController::class, 'update'])->middleware('admin');
    Route::delete('/lots/{id}', [LotController::class, 'destroy'])->middleware('admin');

    // Retours
    Route::get('/retours', [RetourController::class, 'index']);
    Route::get('/retours/all', [RetourController::class, 'all']);
    Route::get('/retours/{id}', [RetourController::class, 'show']);
    Route::post('/retours', [RetourController::class, 'store'])->middleware('admin');
    Route::put('/retours/{id}', [RetourController::class, 'update'])->middleware('admin');
    Route::delete('/retours/{id}', [RetourController::class, 'destroy'])->middleware('admin');

    // Lignes de retour
    Route::get('/ligne-retours', [LigneRetourController::class, 'index']);
    Route::get('/ligne-retours/all', [LigneRetourController::class, 'all']);
    Route::get('/ligne-retours/{id}', [LigneRetourController::class, 'show']);
    Route::post('/ligne-retours', [LigneRetourController::class, 'store'])->middleware('admin');
    Route::put('/ligne-retours/{id}', [LigneRetourController::class, 'update'])->middleware('admin');
    Route::delete('/ligne-retours/{id}', [LigneRetourController::class, 'destroy'])->middleware('admin');

    // Caisses
    Route::get('/caisses', [CaisseController::class, 'index']);
    Route::get('/caisses/all', [CaisseController::class, 'all']);
    Route::get('/caisses/{id}', [CaisseController::class, 'show']);
    Route::post('/caisses', [CaisseController::class, 'store'])->middleware('admin');
    Route::put('/caisses/{id}', [CaisseController::class, 'update'])->middleware('admin');
    Route::delete('/caisses/{id}', [CaisseController::class, 'destroy'])->middleware('admin');

    // Mouvements de stock FIFO
    Route::get('/mouvements-stock-fifos', [MouvementStockFifoController::class, 'index']);
    Route::get('/mouvements-stock-fifos/all', [MouvementStockFifoController::class, 'all']);
    Route::get('/mouvements-stock-fifos/{id}', [MouvementStockFifoController::class, 'show']);
    Route::post('/mouvements-stock-fifos', [MouvementStockFifoController::class, 'store'])->middleware('admin');
    Route::put('/mouvements-stock-fifos/{id}', [MouvementStockFifoController::class, 'update'])->middleware('admin');
    Route::delete('/mouvements-stock-fifos/{id}', [MouvementStockFifoController::class, 'destroy'])->middleware('admin');

    // Transactions de caisse
    Route::get('/transactions-caisses', [TransactionCaisseController::class, 'index']);
    Route::get('/transactions-caisses/all', [TransactionCaisseController::class, 'all']);
    Route::get('/transactions-caisses/{id}', [TransactionCaisseController::class, 'show']);
    Route::post('/transactions-caisses', [TransactionCaisseController::class, 'store'])->middleware('admin');
    Route::put('/transactions-caisses/{id}', [TransactionCaisseController::class, 'update'])->middleware('admin');
    Route::delete('/transactions-caisses/{id}', [TransactionCaisseController::class, 'destroy'])->middleware('admin');

    // Users Routes - Admin uniquement
    Route::get('/users', [UserController::class, 'index'])->middleware('admin');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('admin');
    Route::post('/users', [UserController::class, 'store'])->middleware('admin');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('admin');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('admin');
});

// ==================== ROUTES AUTHENTIFIÉES VENDEUR ====================
Route::middleware('vendeur')->group(function () {
    
    // Auth vendeur routes
    Route::get('/vendeur/user', function (Request $request) {
        return response()->json([
            'vendeur' => $request->user()
        ]);
    });
    Route::post('/vendeur/logout', [AuthController::class, 'logoutVendeur']);

    // Routes accessibles aux vendeurs (ventes, clients, etc.)
    Route::get('/vendeur/clients', [ClientController::class, 'index']);
    Route::get('/vendeur/clients/{id}', [ClientController::class, 'show']);
    Route::post('/vendeur/clients', [ClientController::class, 'store']);
    Route::put('/vendeur/clients/{id}', [ClientController::class, 'update']);

    Route::get('/vendeur/produits', [ProduitController::class, 'index']);
    Route::get('/vendeur/produits/{id}', [ProduitController::class, 'show']);

    Route::get('/vendeur/ventes', [VenteController::class, 'index']);
    Route::get('/vendeur/ventes/{id}', [VenteController::class, 'show']);
    Route::post('/vendeur/ventes', [VenteController::class, 'store']);
    Route::put('/vendeur/ventes/{id}', [VenteController::class, 'update']);

    Route::get('/vendeur/caisses', [CaisseController::class, 'index']);
    Route::get('/vendeur/caisses/{id}', [CaisseController::class, 'show']);
});
