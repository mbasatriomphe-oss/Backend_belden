<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ligne_approvisionnements extends Model
{
    use HasFactory;

    protected $table = 'ligne_approvisionnements';

    protected $fillable = [
        'id_approvisionnement',
        'id_produit',
        'quantite',
        'prix_unitaire',
        'prix_vente',
        'id_devise',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:8',
        'prix_vente' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
