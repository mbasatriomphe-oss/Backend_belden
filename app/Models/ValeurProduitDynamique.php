<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValeurProduitDynamique extends Model
{
    use HasFactory;

    protected $table = 'valeurs_produit_dynamiques';

    protected $fillable = [
        'produit_id',
        'attribut_template_id',
        'valeur',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function attributTemplate()
    {
        return $this->belongsTo(AttributTemplate::class, 'attribut_template_id');
    }
}
