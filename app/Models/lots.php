<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class lots extends Model
{
    use HasFactory;

    protected $table = 'lots';

    protected $fillable = [
        'numero_lot',
        'id_produit',
        'id_approvisionnement',
        'id_ligne_approvisionnement',
        'quantite_initial',
        'date_reception',
        'date_expiration',
        'id_devise',
    ];

    protected $casts = [
        'date_reception' => 'date',
        'date_expiration' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
