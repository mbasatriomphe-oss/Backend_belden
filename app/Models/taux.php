<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class taux extends Model
{
    use HasFactory;

    protected $table = 'taux';

    protected $fillable = [
        'devise_source',
        'devise_but',
        'valeur',
        'date_effet',
        'statut',
    ];

    protected $casts = [
        'date_effet' => 'date',
        'valeur' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
