<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class approvisionnements extends Model
{
    use HasFactory;

    protected $table = 'approvisionnements';

    protected $fillable = [
        'code',
        'date',
        'id_user',
        'id_fournisseur',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
