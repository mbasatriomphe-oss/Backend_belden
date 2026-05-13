<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class clients extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'nom',
        'post_nom',
        'prenom',
        'adresse',
        'ville',
        'pays',
        'contact',
        'iduser',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
