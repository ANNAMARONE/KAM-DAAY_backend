<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    /** @use HasFactory<\Database\Factories\ProduitFactory> */
    use HasFactory;
    protected $guarded=[];
    public function ventes()
    {
        return $this->belongsToMany(Vente::class, 'produit_ventes') // idem ici
            ->withPivot(['quantite', 'prix_unitaire', 'montant_total', 'date_vente'])
            ->withTimestamps();
    }
    

}