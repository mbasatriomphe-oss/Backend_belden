<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributTemplate extends Model
{
    use HasFactory;

    protected $table = 'attributs_templates';

    protected $fillable = [
        'categorie_id',
        'attribut_id',
        'ordre_affichage',
        'obligatoire',
        'est_visuel',
    ];

    public function categorie()
    {
        return $this->belongsTo(categories::class, 'categorie_id');
    }

    public function attribut()
    {
        return $this->belongsTo(Attribut::class, 'attribut_id');
    }

    public function valeursDynamiques()
    {
        return $this->hasMany(ValeurProduitDynamique::class, 'attribut_template_id');
    }

    public function photosProduit()
    {
        return $this->hasMany(PhotoProduit::class, 'attribut_template_id');
    }
}
