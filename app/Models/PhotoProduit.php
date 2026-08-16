<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoProduit extends Model
{
    use HasFactory;

    protected $table = 'photos_produits';

    protected $fillable = [
        'produit_id',
        'attribut_template_id',
        'valeur_attribut',
        'chemin',
        'nom_original',
        'ordre',
        'legende',
        'est_principale',
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
