<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class produits extends Model
{
    use HasFactory;

    protected $table = 'produits';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'photo',
        'unite_id',
        'categorie_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
