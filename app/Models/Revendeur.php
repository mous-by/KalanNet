<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revendeur extends Model
{
    protected $table = 'revendeurs';

    protected $fillable = [
        'nom',
        'numero_orange_wave',
        'numero_mobicash',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function offres()
    {
        return $this->hasMany(RevendeurOffre::class, 'id_revendeur');
    }

    public function utilisateur()
    {
        return $this->hasOne(User::class, 'id_revendeur');
    }

    public function ecoles()
    {
        return $this->hasMany(Ecole::class, 'id_revendeur', 'id');
    }
}
