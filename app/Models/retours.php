<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class retours extends Model
{
    use HasFactory;

    protected $table = 'retours';

    protected $fillable = [
        'code',
        'date_retour',
        'id_vente',
        'id_client',
        'id_vendeur',
        'motif',
        'commentaire',
    ];

    protected $casts = [
        'date_retour' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
