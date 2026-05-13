<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ligne_retours extends Model
{
    use HasFactory;

    protected $table = 'ligne_retours';

    protected $fillable = [
        'id_retour',
        'id_produit',
        'id_ligne_vente',
        'id_lot',
        'quantite_retournee',
        'prix_vente_original',
        'prix_remboursement',
        'montant_penalite',
        'prix_unitaire_lot',
        'raison_difference',
        'justification_difference',
        'etat_produit',
        'reintegre_stock',
        'id_devise',
    ];

    protected $casts = [
        'prix_vente_original' => 'decimal:8',
        'prix_remboursement' => 'decimal:8',
        'montant_penalite' => 'decimal:8',
        'prix_unitaire_lot' => 'decimal:8',
        'reintegre_stock' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
