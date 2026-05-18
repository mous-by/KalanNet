<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Academie extends Model
{
    protected $table = 'academie';
    protected $primaryKey = 'id_academie';

    protected $fillable = [
        'nom_academie',
        'code_academie',
        'localite_academie',
    ];

    public function caps()
    {
        return $this->hasMany(Cap::class, 'id_academie', 'id_academie');
    }

    public function ecoles()
    {
        return $this->hasMany(Ecole::class, 'id_academie', 'id_academie');
    }
}
