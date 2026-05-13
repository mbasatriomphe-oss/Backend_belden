<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ligne_ventes extends Model
{
    use HasFactory;

    protected $table = 'ligne_ventes';

    protected $fillable = [
        'id_vente',
        'id_produit',
        'quantite',
        'prix_vente',
        'id_devise',
    ];

    protected $casts = [
        'prix_vente' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
