<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackFactory> */
    protected $guarded=[];
    public function vente()
{
    return $this->belongsTo(Vente::class);
}
public function client()
{
    // Supposons que la table feedback a une colonne client_id
    return $this->belongsTo(Client::class);
}


}