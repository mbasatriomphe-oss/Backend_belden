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
        Schema::create('photos_produits', function (Blueprint $table) {
            $table->id();
            // Lien vers le produit parent
            $table->unsignedBigInteger('produit_id');
            
            // Lien vers le template (pour savoir quel attribut visuel)
            $table->unsignedBigInteger('attribut_template_id');
            
            // La valeur de l'attribut visuel (ex: 'Noir', 'Rouge')
            $table->string('valeur_attribut', 100);
            
            // Les photos
            $table->string('chemin', 255);
            $table->string('nom_original', 255)->nullable();
            $table->integer('ordre')->default(0);
            $table->string('legende', 255)->nullable();
            $table->boolean('est_principale')->default(false);
            
            $table->timestamps();
            
            // Clés étrangères
            $table->foreign('produit_id')
                  ->references('id')
                  ->on('produits')
                  ->onDelete('cascade');
                  
            $table->foreign('attribut_template_id')
                  ->references('id')
                  ->on('attributs_templates')
                  ->onDelete('cascade');
            
            // Index pour performance
            $table->index('produit_id');
            $table->index('attribut_template_id');
            $table->index('est_principale');
            
            // Un produit ne peut pas avoir 2 fois la même photo pour la même valeur
            $table->unique(['produit_id', 'attribut_template_id', 'valeur_attribut', 'chemin'], 'unique_photo_produit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos_produits');
    }
};
