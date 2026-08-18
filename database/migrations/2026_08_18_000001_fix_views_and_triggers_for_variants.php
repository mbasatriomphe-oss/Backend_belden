<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS create_lot_after_approvisionnement');
        DB::unprepared('DROP TRIGGER IF EXISTS update_stock_after_vente_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS reintegrer_stock_after_retour');

        DB::unprepared('
            CREATE TRIGGER create_lot_after_approvisionnement
            AFTER INSERT ON ligne_approvisionnements
            FOR EACH ROW
            BEGIN
                DECLARE lot_number VARCHAR(50);
                DECLARE appro_date DATE;
                DECLARE product_id BIGINT;

                IF NEW.id_variante_produit IS NULL THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Une ligne d’approvisionnement doit avoir une variante pour calculer le produit associé";
                END IF;

                SELECT produit_id INTO product_id
                FROM variantes_produits
                WHERE id = NEW.id_variante_produit;

                IF product_id IS NULL THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Variante de produit introuvable pour l’approvisionnement";
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
                    created_at
                ) VALUES (
                    LAST_INSERT_ID(),
                    "entree",
                    NEW.quantite,
                    0,
                    NEW.quantite,
                    appro_date,
                    NOW()
                );
            END
        ');

        DB::unprepared('
            CREATE TRIGGER update_stock_after_vente_insert
            AFTER INSERT ON ligne_ventes
            FOR EACH ROW
            BEGIN
                DECLARE quantite_restante INT;
                DECLARE lot_id INT;
                DECLARE quantite_lot INT;
                DECLARE done INT DEFAULT FALSE;
                DECLARE product_id BIGINT;
                DECLARE cur_lots CURSOR FOR
                    SELECT l.id,
                           l.quantite_initial - COALESCE((
                               SELECT SUM(m.quantite)
                               FROM mouvements_stock_fifos m
                               WHERE m.id_lot = l.id
                                 AND m.type_mouvement = "sortie"
                           ), 0) AS disponible
                    FROM lots l
                    WHERE l.id_produit = product_id
                      AND l.id_variante_produit = NEW.id_variante_produit
                      AND (
                          l.quantite_initial - COALESCE((
                              SELECT SUM(m.quantite)
                              FROM mouvements_stock_fifos m
                              WHERE m.id_lot = l.id
                                AND m.type_mouvement = "sortie"
                          ), 0)
                      ) > 0
                    ORDER BY l.date_reception ASC;
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

                IF NEW.id_variante_produit IS NULL THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Une ligne de vente doit avoir une variante pour calculer le produit associé";
                END IF;

                SELECT produit_id INTO product_id
                FROM variantes_produits
                WHERE id = NEW.id_variante_produit;

                IF product_id IS NULL THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Variante de produit introuvable pour la vente";
                END IF;

                SET quantite_restante = NEW.quantite;

                OPEN cur_lots;

                lire_loop: LOOP
                    FETCH cur_lots INTO lot_id, quantite_lot;
                    IF done OR quantite_restante <= 0 THEN
                        LEAVE lire_loop;
                    END IF;

                    IF quantite_lot >= quantite_restante THEN
                        INSERT INTO mouvements_stock_fifos (
                            id_lot,
                            id_ligne_vente,
                            type_mouvement,
                            quantite,
                            quantite_restante_avant,
                            quantite_restante_apres,
                            date_mouvement,
                            created_at
                        ) VALUES (
                            lot_id,
                            NEW.id,
                            "sortie",
                            quantite_restante,
                            quantite_lot,
                            quantite_lot - quantite_restante,
                            CURDATE(),
                            NOW()
                        );
                        SET quantite_restante = 0;
                    ELSE
                        INSERT INTO mouvements_stock_fifos (
                            id_lot,
                            id_ligne_vente,
                            type_mouvement,
                            quantite,
                            quantite_restante_avant,
                            quantite_restante_apres,
                            date_mouvement,
                            created_at
                        ) VALUES (
                            lot_id,
                            NEW.id,
                            "sortie",
                            quantite_lot,
                            quantite_lot,
                            0,
                            CURDATE(),
                            NOW()
                        );
                        SET quantite_restante = quantite_restante - quantite_lot;
                    END IF;
                END LOOP;

                CLOSE cur_lots;

                IF quantite_restante > 0 THEN
                    SIGNAL SQLSTATE "45000"
                    SET MESSAGE_TEXT = "Stock insuffisant pour cette vente";
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER reintegrer_stock_after_retour
            AFTER INSERT ON ligne_retours
            FOR EACH ROW
            BEGIN
                DECLARE stock_actuel INT;

                SET stock_actuel = (
                    SELECT l.quantite_initial - COALESCE((
                        SELECT SUM(m.quantite)
                        FROM mouvements_stock_fifos m
                        WHERE m.id_lot = l.id
                          AND m.type_mouvement = "sortie"
                    ), 0)
                    FROM lots l
                    WHERE l.id = NEW.id_lot
                );

                IF NEW.etat_produit = "bon" AND NEW.reintegre_stock = TRUE THEN
                    INSERT INTO mouvements_stock_fifos (
                        id_lot,
                        id_ligne_retour,
                        type_mouvement,
                        quantite,
                        quantite_restante_avant,
                        quantite_restante_apres,
                        date_mouvement,
                        created_at
                    ) VALUES (
                        NEW.id_lot,
                        NEW.id,
                        "retour",
                        NEW.quantite_retournee,
                        stock_actuel,
                        stock_actuel + NEW.quantite_retournee,
                        CURDATE(),
                        NOW()
                    );
                END IF;
            END
        ');

        DB::statement('DROP VIEW IF EXISTS v_stock_disponible_variantes');
        DB::statement('DROP VIEW IF EXISTS v_stock_disponible');
        DB::statement('DROP VIEW IF EXISTS v_lots_expiration');
        DB::statement('DROP VIEW IF EXISTS v_chiffre_affaires');
        DB::statement('DROP VIEW IF EXISTS v_top_produits');
        DB::statement('DROP VIEW IF EXISTS v_marge_produit');
        DB::statement('DROP VIEW IF EXISTS v_mouvements_caisse');
        DB::statement('DROP VIEW IF EXISTS v_recap_journalier');
        DB::statement('DROP VIEW IF EXISTS v_etat_caisses');

        DB::statement('
            CREATE OR REPLACE VIEW v_stock_disponible AS
            SELECT
                p.id,
                p.code,
                p.nom,
                p.description,
                u.nom AS unite_nom,
                u.symbole AS unite_symbole,
                COALESCE(
                    SUM(
                        l.quantite_initial - COALESCE((
                            SELECT SUM(mf.quantite)
                            FROM mouvements_stock_fifos mf
                            WHERE mf.id_lot = l.id
                              AND mf.type_mouvement = "sortie"
                        ), 0)
                    ),
                    0
                ) AS stock_actuel
            FROM produits p
            LEFT JOIN unites u ON u.id = p.unite_id
            LEFT JOIN lots l ON l.id_produit = p.id
            GROUP BY p.id, p.code, p.nom, p.description, u.nom, u.symbole
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_stock_disponible_variantes AS
            SELECT
                p.id AS produit_id,
                p.code AS produit_code,
                p.nom AS produit_nom,
                u.nom AS unite_nom,
                u.symbole AS unite_symbole,
                vp.id AS id_variante_produit,
                vp.code_sku,
                vp.combinaison,
                COALESCE(
                    SUM(
                        l.quantite_initial - COALESCE((
                            SELECT SUM(mf.quantite)
                            FROM mouvements_stock_fifos mf
                            WHERE mf.id_lot = l.id
                              AND mf.type_mouvement = "sortie"
                        ), 0)
                    ),
                    0
                ) AS stock_actuel
            FROM produits p
            LEFT JOIN unites u ON u.id = p.unite_id
            LEFT JOIN lots l ON l.id_produit = p.id
            LEFT JOIN variantes_produits vp ON vp.id = l.id_variante_produit
            GROUP BY
                p.id,
                p.code,
                p.nom,
                u.nom,
                u.symbole,
                vp.id,
                vp.code_sku,
                vp.combinaison
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_lots_expiration AS
            SELECT
                l.id,
                l.numero_lot,
                p.nom AS produit_nom,
                l.quantite_initial,
                (l.quantite_initial - COALESCE((
                    SELECT SUM(mf.quantite)
                    FROM mouvements_stock_fifos mf
                    WHERE mf.id_lot = l.id
                      AND mf.type_mouvement = "sortie"
                ), 0)) AS quantite_restante,
                l.date_reception,
                l.date_expiration,
                DATEDIFF(l.date_expiration, CURDATE()) AS jours_restants,
                CASE
                    WHEN l.date_expiration < CURDATE() THEN "Expiré"
                    WHEN DATEDIFF(l.date_expiration, CURDATE()) <= 30 THEN "Expire bientôt"
                    ELSE "Valide"
                END AS statut_expiration
            FROM lots l
            INNER JOIN produits p ON p.id = l.id_produit
            WHERE (l.quantite_initial - COALESCE((
                SELECT SUM(mf.quantite)
                FROM mouvements_stock_fifos mf
                WHERE mf.id_lot = l.id
                  AND mf.type_mouvement = "sortie"
            ), 0)) > 0
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_etat_caisses AS
            SELECT
                d.code AS devise_code,
                d.nom AS devise_nom,
                d.symbole AS devise_symbole,
                c.solde,
                c.updated_at AS derniere_mise_a_jour,
                (
                    SELECT SUM(tc.montant)
                    FROM transactions_caisses tc
                    WHERE tc.id_caisse = c.id
                      AND tc.type = "entree"
                      AND DATE(tc.created_at) = CURDATE()
                ) AS entree_jour,
                (
                    SELECT SUM(tc.montant)
                    FROM transactions_caisses tc
                    WHERE tc.id_caisse = c.id
                      AND tc.type = "sortie"
                      AND DATE(tc.created_at) = CURDATE()
                ) AS sortie_jour
            FROM caisses c
            INNER JOIN devises d ON d.id = c.id_devise
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_chiffre_affaires AS
            SELECT
                DATE(v.date) AS date_vente,
                MONTH(v.date) AS mois,
                YEAR(v.date) AS annee,
                d.code AS devise_code,
                COUNT(DISTINCT v.id) AS nombre_ventes,
                SUM(lv.quantite) AS quantite_totale,
                SUM(lv.quantite * lv.prix_vente) AS montant_total
            FROM ventes v
            INNER JOIN ligne_ventes lv ON lv.id_vente = v.id
            INNER JOIN devises d ON d.id = lv.id_devise
            GROUP BY DATE(v.date), MONTH(v.date), YEAR(v.date), d.code
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_top_produits AS
            SELECT
                p.id,
                p.code,
                p.nom,
                c.nom AS categorie_nom,
                COUNT(DISTINCT lv.id_vente) AS nombre_ventes,
                SUM(lv.quantite) AS quantite_vendue,
                SUM(lv.quantite * lv.prix_vente) AS chiffre_affaires
            FROM ligne_ventes lv
            INNER JOIN variantes_produits vp ON vp.id = lv.id_variante_produit
            INNER JOIN produits p ON p.id = vp.produit_id
            INNER JOIN categories c ON c.id = p.categorie_id
            GROUP BY p.id, p.code, p.nom, c.nom
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_marge_produit AS
            SELECT
                p.id,
                p.code,
                p.nom,
                AVG(lv.prix_vente) AS prix_vente_moyen,
                AVG(la.prix_unitaire) AS prix_achat_moyen,
                (AVG(lv.prix_vente) - AVG(la.prix_unitaire)) AS marge_unitaire,
                ((AVG(lv.prix_vente) - AVG(la.prix_unitaire)) / AVG(lv.prix_vente) * 100) AS marge_pourcentage
            FROM ligne_ventes lv
            INNER JOIN variantes_produits vp ON vp.id = lv.id_variante_produit
            INNER JOIN produits p ON p.id = vp.produit_id
            INNER JOIN lots l ON l.id_variante_produit = vp.id
            INNER JOIN ligne_approvisionnements la ON la.id = l.id_ligne_approvisionnement
            GROUP BY p.id, p.code, p.nom
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_mouvements_caisse AS
            SELECT
                tc.id,
                d.code AS devise_code,
                tc.type,
                tc.montant,
                tc.reference_type,
                tc.reference_id,
                tc.description,
                tc.solde_avant,
                tc.solde_apres,
                tc.created_at AS date_mouvement,
                u.nom AS utilisateur_nom,
                u.prenom AS utilisateur_prenom
            FROM transactions_caisses tc
            INNER JOIN caisses c ON c.id = tc.id_caisse
            INNER JOIN devises d ON d.id = c.id_devise
            LEFT JOIN users u ON u.id = tc.created_by
        ');

        DB::statement('
            CREATE OR REPLACE VIEW v_recap_journalier AS
            SELECT
                CURDATE() AS date_jour,
                (SELECT COUNT(*) FROM ventes WHERE DATE(date) = CURDATE()) AS total_ventes,
                (SELECT SUM(lv.quantite * lv.prix_vente)
                 FROM ventes v
                 INNER JOIN ligne_ventes lv ON lv.id_vente = v.id
                 WHERE DATE(v.date) = CURDATE()) AS chiffre_affaires_jour,
                (SELECT COUNT(*) FROM retours WHERE DATE(date_retour) = CURDATE()) AS total_retours,
                (SELECT SUM(lr.prix_remboursement * lr.quantite_retournee)
                 FROM retours r
                 INNER JOIN ligne_retours lr ON lr.id_retour = r.id
                 WHERE DATE(r.date_retour) = CURDATE()) AS total_remboursements,
                (SELECT COUNT(*) FROM approvisionnements WHERE DATE(date) = CURDATE()) AS total_approvisionnements
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS create_lot_after_approvisionnement');
        DB::unprepared('DROP TRIGGER IF EXISTS update_stock_after_vente_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS reintegrer_stock_after_retour');

        DB::statement('DROP VIEW IF EXISTS v_stock_disponible_variantes');
        DB::statement('DROP VIEW IF EXISTS v_stock_disponible');
        DB::statement('DROP VIEW IF EXISTS v_lots_expiration');
        DB::statement('DROP VIEW IF EXISTS v_chiffre_affaires');
        DB::statement('DROP VIEW IF EXISTS v_top_produits');
        DB::statement('DROP VIEW IF EXISTS v_marge_produit');
        DB::statement('DROP VIEW IF EXISTS v_mouvements_caisse');
        DB::statement('DROP VIEW IF EXISTS v_recap_journalier');
        DB::statement('DROP VIEW IF EXISTS v_etat_caisses');
    }
};
