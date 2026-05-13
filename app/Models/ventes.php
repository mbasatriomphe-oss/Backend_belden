<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ventes extends Model
{
    use HasFactory;

    protected $table = 'ventes';

    protected $fillable = [
        'code',
        'date',
        'id_vendeur',
        'id_client',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
