<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('nom', 100);
            $table->text('description')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedBigInteger('unite_id')->nullable();
            $table->unsignedBigInteger('categorie_id');
            $table->boolean('has_variantes')->default(false);
            $table->decimal('prix_achat', 20, 8)->nullable();
            $table->decimal('prix_vente', 20, 8)->nullable();
            $table->integer('quantite_stock')->nullable();
            $table->foreign('unite_id')->references('id')->on('unites')->nullOnDelete();
            $table->foreign('categorie_id')->references('id')->on('categories');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
