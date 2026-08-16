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
        'quantite_stock',
        'seuil_alerte',
    ];

    protected $casts = [
        'combinaison' => 'array',
        'quantite_stock' => 'integer',
        'seuil_alerte' => 'integer',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
