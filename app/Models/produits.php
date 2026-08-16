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
        'has_variantes',
        'prix_achat',
        'prix_vente',
        'quantite_stock',
    ];

    protected $casts = [
        'has_variantes' => 'boolean',
        'prix_achat' => 'decimal:8',
        'prix_vente' => 'decimal:8',
        'quantite_stock' => 'integer',
    ];

    public function unite()
    {
        return $this->belongsTo(unites::class, 'unite_id')->withDefault();
    }

    public function categorie()
    {
        return $this->belongsTo(categories::class, 'categorie_id');
    }

    public function lignesApprovisionnement()
    {
        return $this->hasMany(ligne_approvisionnements::class, 'id_produit');
    }

    public function lignesVente()
    {
        return $this->hasMany(ligne_ventes::class, 'id_produit');
    }

    public function lots()
    {
        return $this->hasMany(lots::class, 'id_produit');
    }

    public function lignesRetour()
    {
        return $this->hasMany(ligne_retours::class, 'id_produit');
    }

    public function variantes()
    {
        return $this->hasMany(VarianteProduit::class, 'produit_id');
    }

    public function valeursDynamiques()
    {
        return $this->hasMany(ValeurProduitDynamique::class, 'produit_id');
    }

    public function photosProduit()
    {
        return $this->hasMany(PhotoProduit::class, 'produit_id');
    }

    public function isAvecVariantes(): bool
    {
        return (bool) $this->has_variantes;
    }

    public function hasVariantes(): bool
    {
        return $this->isAvecVariantes();
    }
}
