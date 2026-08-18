<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['ligne_ventes', 'ligne_approvisionnements', 'ligne_retours'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'id_produit')) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'id_variante_produit')) {
                DB::statement("
                    UPDATE `{$tableName}` AS t
                    LEFT JOIN `variantes_produits` AS vp ON vp.id = t.id_variante_produit
                    SET t.id_produit = vp.produit_id
                    WHERE t.id_variante_produit IS NOT NULL
                      AND (t.id_produit IS NULL OR t.id_produit <> vp.produit_id)
                ");
            }

            try {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['id_produit']);
                });
            } catch (\Throwable $e) {
                // Foreign key may not exist or may have a different generated name.
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('id_produit');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['ligne_ventes', 'ligne_approvisionnements', 'ligne_retours'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'id_produit')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('id_produit')->nullable()->after('id_variante_produit');
            });

            if (Schema::hasColumn($tableName, 'id_variante_produit')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreign('id_produit')->references('id')->on('produits')->onDelete('restrict');
                });
            }
        }
    }
};
