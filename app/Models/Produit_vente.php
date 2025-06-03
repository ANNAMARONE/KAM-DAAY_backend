<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit_vente extends Model
{
    /** @use HasFactory<\Database\Factories\ProduitVenteFactory> */
    use HasFactory;
    protected $guarded = [];
}