<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vente extends Model
{
    /** @use HasFactory<\Database\Factories\VenteFactory> */
    protected $guarded=[];
    public function user()
{
    return $this->belongsTo(User::class);
}

public function client()
{
    return $this->belongsTo(Client::class);
}
public function feedback()
{
    return $this->hasOne(Feedback::class);
}
public function produits()
{
    return $this->belongsToMany(Produit::class, 'produit_ventes') // ici on précise le nom de la table pivot
        ->withPivot(['quantite', 'prix_unitaire', 'montant_total', 'date_vente'])
        ->withTimestamps();
}


}