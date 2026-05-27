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
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function vendeur()
    {
        return $this->belongsTo(vendeurs::class, 'id_vendeur');
    }

    public function client()
    {
        return $this->belongsTo(clients::class, 'id_client');
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
