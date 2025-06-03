<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    protected $guarded=[];
    use SoftDeletes;
    public function user()
{
    return $this->belongsTo(User::class);
}
public function ventes()
{
    return $this->hasMany(Vente::class);
}


}