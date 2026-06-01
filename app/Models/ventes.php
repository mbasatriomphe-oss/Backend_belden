<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ventes extends Model
{
    use HasFactory;

    protected $table = 'ventes';

    protected $fillable = [
        'code',
        'date',
        'id_vendeur',
        'id_client',
        'devise_vente_id',
        'montant_total',
        'montant_paye',
        'reste_a_payer',
        'statut_paiement',
    ];

    protected $casts = [
        'date' => 'date',
        'montant_total' => 'decimal:8',
        'montant_paye' => 'decimal:8',
        'reste_a_payer' => 'decimal:8',
    ];

    public function vendeur()
    {
        return $this->belongsTo(vendeurs::class, 'id_vendeur');
    }

    public function client()
    {
        return $this->belongsTo(clients::class, 'id_client');
    }

    public function deviseVente()
    {
        return $this->belongsTo(devise::class, 'devise_vente_id');
    }

    public function lignes()
    {
        return $this->hasMany(ligne_ventes::class, 'id_vente');
    }

    public function retours()
    {
        return $this->hasMany(retours::class, 'id_vente');
    }

    public function transactionsCaisses()
    {
        return $this->hasMany(transactions_caisses::class, 'reference_id')
            ->where('reference_type', 'vente');
    }
}
