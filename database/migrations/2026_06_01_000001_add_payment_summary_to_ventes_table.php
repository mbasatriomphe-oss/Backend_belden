<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->unsignedBigInteger('devise_vente_id')->nullable()->after('id_client');
            $table->decimal('montant_total', 20, 8)->default(0)->after('devise_vente_id');
            $table->decimal('montant_paye', 20, 8)->default(0)->after('montant_total');
            $table->decimal('reste_a_payer', 20, 8)->default(0)->after('montant_paye');
            $table->string('statut_paiement', 20)->default('payee')->after('reste_a_payer');

            $table->foreign('devise_vente_id')->references('id')->on('devises')->nullOnDelete();
            $table->index(['id_client', 'reste_a_payer']);
            $table->index(['statut_paiement']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ventes', 'devise_vente_id')) {
            $fk = DB::selectOne(
                "SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'ventes'
                   AND COLUMN_NAME = 'devise_vente_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );

            if ($fk && !empty($fk->CONSTRAINT_NAME)) {
                DB::statement('ALTER TABLE ventes DROP FOREIGN KEY `' . $fk->CONSTRAINT_NAME . '`');
            }
        }

        $indexName = 'ventes_statut_paiement_index';
        $exists = DB::selectOne(
            "SELECT 1
             FROM information_schema.statistics
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'ventes'
               AND INDEX_NAME = ?
             LIMIT 1",
            [$indexName]
        );

        if ($exists) {
            DB::statement('ALTER TABLE ventes DROP INDEX `' . $indexName . '`');
        }

        if (Schema::hasColumn('ventes', 'devise_vente_id')) {
            Schema::table('ventes', function (Blueprint $table) {
                $table->dropColumn(['devise_vente_id', 'montant_total', 'montant_paye', 'reste_a_payer', 'statut_paiement']);
            });
        }
    }
};