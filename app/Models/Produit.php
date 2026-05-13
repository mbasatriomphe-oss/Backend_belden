<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'description', 'prix_unitaire', 'quantite_stock', 'categorie_id', 'unite_id'];

    public function categorie() { return $this->belongsTo(Categorie::class); }
    public function unite() { return $this->belongsTo(Unite::class); }
}