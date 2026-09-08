<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ligne_approvisionnements', 'id_produit')) {
            Schema::table('ligne_approvisionnements', function (Blueprint $table): void {
                $table->unsignedBigInteger('id_produit')->nullable()->after('id_variante_produit');
                $table->foreign('id_produit')->references('id')->on('produits')->onDelete('restrict');
                $table->index('id_produit');
            });
        }

        DB::unprepared('DROP TRIGGER IF EXISTS create_lot_after_approvisionnement');

        DB::unprepared('
            CREATE TRIGGER create_lot_after_approvisionnement
            AFTER INSERT ON ligne_approvisionnements
            FOR EACH ROW
            BEGIN
                DECLARE lot_number VARCHAR(50);
                DECLARE appro_date DATE;
                DECLARE product_id BIGINT;

                IF NEW.id_variante_produit IS NULL THEN
                    SET product_id = NEW.id_produit;
                ELSE
                    SELECT produit_id INTO product_id
                    FROM variantes_produits
                    WHERE id = NEW.id_variante_produit;

                    IF product_id IS NULL THEN
                        SIGNAL SQLSTATE "45000"
                        SET MESSAGE_TEXT = "Variante de produit introuvable pour l’approvisionnement";
                    END IF;

                    IF product_id <> NEW.id_produit THEN
                        SIGNAL SQLSTATE "45000"
                        SET MESSAGE_TEXT = "La variante ne correspond pas au produit de l’approvisionnement";
                    END IF;
                END IF;

                SELECT date INTO appro_date
                FROM approvisionnements
                WHERE id = NEW.id_approvisionnement;

                SET lot_number = CONCAT(
                    "LOT-",
                    DATE_FORMAT(appro_date, "%Y%m%d"),
                    "-",
                    LPAD(product_id, 5, "0"),
                    "-",
                    LPAD(FLOOR(RAND() * 10000), 4, "0")
                );

                INSERT INTO lots (
                    numero_lot,
                    id_produit,
                    id_variante_produit,
                    id_approvisionnement,
                    id_ligne_approvisionnement,
                    quantite_initial,
                    date_reception,
                    id_devise,
                    created_at,
                    updated_at
                ) VALUES (
                    lot_number,
                    product_id,
                    NEW.id_variante_produit,
                    NEW.id_approvisionnement,
                    NEW.id,
                    NEW.quantite,
                    appro_date,
                    NEW.id_devise,
                    NOW(),
                    NOW()
                );

                INSERT INTO mouvements_stock_fifos (
                    id_lot,
                    type_mouvement,
                    quantite,
                    quantite_restante_avant,
                    quantite_restante_apres,
                    date_mouvement,
                    created_at,
                    updated_at
                ) VALUES (
                    LAST_INSERT_ID(),
                    "entree",
                    NEW.quantite,
                    0,
                    NEW.quantite,
                    appro_date,
                    NOW(),
                    NOW()
                );
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS create_lot_after_approvisionnement');

        if (Schema::hasColumn('ligne_approvisionnements', 'id_produit')) {
            Schema::table('ligne_approvisionnements', function (Blueprint $table): void {
                $table->dropForeign(['id_produit']);
                $table->dropIndex(['id_produit']);
                $table->dropColumn('id_produit');
            });
        }
    }
};
