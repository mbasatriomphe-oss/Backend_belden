<?php

namespace Tests\Unit;

use App\Models\Produit;
use App\Models\VarianteProduit;
use App\Services\VarianteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VarianteServiceTest extends TestCase
{
    protected VarianteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('unites', function ($table) {
            $table->id();
            $table->string('nom');
            $table->timestamps();
        });

        Schema::create('produits', function ($table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->unsignedBigInteger('categorie_id')->nullable();
            $table->unsignedBigInteger('unite_id')->nullable();
            $table->boolean('has_variantes')->default(false);
            $table->decimal('prix_achat', 20, 8)->nullable();
            $table->decimal('prix_vente', 20, 8)->nullable();
            $table->integer('quantite_stock')->nullable();
            $table->timestamps();
        });

        Schema::create('attributs', function ($table) {
            $table->id();
            $table->string('nom');
            $table->string('type_affichage')->default('text');
            $table->timestamps();
        });

        Schema::create('attributs_templates', function ($table) {
            $table->id();
            $table->unsignedBigInteger('categorie_id');
            $table->unsignedBigInteger('attribut_id');
            $table->integer('ordre_affichage')->default(0);
            $table->boolean('obligatoire')->default(true);
            $table->boolean('est_visuel')->default(false);
            $table->timestamps();
        });

        Schema::create('variantes_produits', function ($table) {
            $table->id();
            $table->unsignedBigInteger('produit_id');
            $table->string('code_sku')->unique();
            $table->json('combinaison')->nullable();
            $table->integer('quantite_stock')->default(0);
            $table->integer('seuil_alerte')->default(5);
            $table->timestamps();
        });

        Schema::create('lots', function ($table) {
            $table->id();
            $table->string('numero_lot');
            $table->unsignedBigInteger('id_produit');
            $table->unsignedBigInteger('id_variante_produit')->nullable();
            $table->unsignedBigInteger('id_approvisionnement')->nullable();
            $table->unsignedBigInteger('id_ligne_approvisionnement')->nullable();
            $table->integer('quantite_initial')->default(0);
            $table->date('date_reception')->nullable();
            $table->timestamps();
        });

        Schema::create('mouvements_stock_fifos', function ($table) {
            $table->id();
            $table->unsignedBigInteger('id_lot');
            $table->string('type_mouvement');
            $table->integer('quantite')->default(0);
            $table->date('date_mouvement')->nullable();
            $table->timestamps();
        });

        $this->service = new VarianteService();
    }

    public function test_generer_combinaisons_et_synchroniser_les_variantes(): void
    {
        $produit = Produit::create([
            'code' => 'PRO-001',
            'nom' => 'T-shirt',
            'categorie_id' => 1,
            'unite_id' => 1,
            'has_variantes' => true,
            'prix_achat' => null,
            'prix_vente' => null,
            'quantite_stock' => null,
        ]);

        $combinaisons = $this->service->genererCombinaisons($produit, [
            'Couleur' => ['Noir', 'Bleu'],
            'Taille' => ['M', 'L'],
        ]);

        $this->assertCount(4, $combinaisons);
        $this->assertSame('Noir', $combinaisons[0]['Couleur']);

        $resultat = $this->service->synchroniserVariantes($produit, [
            'Couleur' => ['Noir', 'Bleu'],
            'Taille' => ['M', 'L'],
        ]);

        $this->assertSame(4, $resultat['creees']);
        $this->assertCount(4, $produit->variantes()->get());

        $this->assertTrue($this->service->mettreAJourStock($produit, ['Couleur' => 'Noir', 'Taille' => 'M'], 12, 'increment'));
        $this->assertSame(12, $this->service->getStockByCombinaison($produit, ['Couleur' => 'Noir', 'Taille' => 'M']));
    }

    public function test_variante_renvoie_le_stock_reel_depuis_les_mouvements_de_lot(): void
    {
        $produit = Produit::create([
            'code' => 'PRO-002',
            'nom' => 'Pull',
            'categorie_id' => 1,
            'unite_id' => 1,
            'has_variantes' => true,
            'prix_achat' => null,
            'prix_vente' => null,
            'quantite_stock' => null,
        ]);

        $variante = VarianteProduit::create([
            'produit_id' => $produit->id,
            'code_sku' => 'PRO-002-NOIR-M',
            'combinaison' => ['Couleur' => 'Noir', 'Taille' => 'M'],
            'quantite_stock' => 0,
            'seuil_alerte' => 5,
        ]);

        DB::table('lots')->insert([
            ['numero_lot' => 'LOT-1', 'id_produit' => $produit->id, 'id_variante_produit' => $variante->id, 'id_approvisionnement' => 1, 'id_ligne_approvisionnement' => 1, 'quantite_initial' => 10, 'date_reception' => '2026-01-01'],
        ]);

        DB::table('mouvements_stock_fifos')->insert([
            ['id_lot' => 1, 'type_mouvement' => 'entree', 'quantite' => 10, 'date_mouvement' => '2026-01-01'],
            ['id_lot' => 1, 'type_mouvement' => 'sortie', 'quantite' => 3, 'date_mouvement' => '2026-01-02'],
        ]);

        $loadedProduit = Produit::with([
            'variantes' => function ($query) {
                $query->selectRaw('variantes_produits.*, COALESCE((
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
        ])->find($produit->id);

        $this->assertSame(7, (int) $loadedProduit->variantes->first()->quantite_stock);
    }
}
