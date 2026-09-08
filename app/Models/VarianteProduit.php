<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VarianteProduit extends Model
{
    use HasFactory;

    protected $table = 'variantes_produits';

    protected $fillable = [
        'produit_id',
        'code_sku',
        'combinaison',
        'prix_vente',
        'quantite_stock',
        'seuil_alerte',
    ];

    protected $casts = [
        'combinaison' => 'array',
        'prix_vente' => 'decimal:2',
        'quantite_stock' => 'integer',
        'seuil_alerte' => 'integer',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
