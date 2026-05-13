<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vendeurs extends Model
{
    use HasFactory;

    protected $table = 'vendeurs';

    protected $fillable = [
        'nom',
        'prenom',
        'code',
        'email',
        'telephone',
        'adresse',
    ];

    public function ventes()
    {
        return $this->hasMany(ventes::class, 'id_vendeur');
    }

    public function retours()
    {
        return $this->hasMany(retours::class, 'id_vendeur');
    }
}
