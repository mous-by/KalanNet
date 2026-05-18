<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cap extends Model
{
    protected $table = 'cap';
    protected $primaryKey = 'id_cap';

    protected $fillable = [
        'nom_cap',
        'code_cap',
        'localite_cap',
        'id_academie',
    ];

    public function academie()
    {
        return $this->belongsTo(Academie::class, 'id_academie', 'id_academie');
    }

    public function ecoles()
    {
        return $this->hasMany(Ecole::class, 'id_cap', 'id_cap');
    }
}
