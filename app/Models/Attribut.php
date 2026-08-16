<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribut extends Model
{
    use HasFactory;

    protected $table = 'attributs';

    protected $fillable = [
        'nom',
        'type_affichage',
    ];

    public function templates()
    {
        return $this->hasMany(AttributTemplate::class, 'attribut_id');
    }
}
