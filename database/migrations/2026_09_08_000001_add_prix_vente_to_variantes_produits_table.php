<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variantes_produits', function (Blueprint $table): void {
            $table->decimal('prix_vente', 15, 2)->nullable()->after('combinaison');
        });
    }

    public function down(): void
    {
        Schema::table('variantes_produits', function (Blueprint $table): void {
            $table->dropColumn('prix_vente');
        });
    }
};