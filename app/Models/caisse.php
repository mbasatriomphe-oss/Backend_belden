<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class caisse extends Model
{
    use HasFactory;

    protected $table = 'caisses';

    protected $fillable = [
        'id_devise',
        'solde',
    ];

    protected $casts = [
        'solde' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
