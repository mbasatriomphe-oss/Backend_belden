<?php
// app/Models/Taux.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taux extends Model
{
    use HasFactory;

    protected $table = 'taux';  // ← AJOUTEZ CETTE LIGNE OBLIGATOIREMENT
    
    protected $fillable = [
        'devise_source',
        'devise_but',
        'valeur',
        'date_effet',
        'statut',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'date_effet' => 'date',
    ];

    public function deviseSource()
    {
        return $this->belongsTo(Devise::class, 'devise_source');
    }

    public function deviseBut()
    {
        return $this->belongsTo(Devise::class, 'devise_but');
    }
}