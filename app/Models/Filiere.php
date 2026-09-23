<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Filiere extends Model
{
    use BelongsToSchool;

    protected $table = 'filieres';
    protected $primaryKey = 'id_filiere';

    protected $fillable = [
        'nom_filiere',
        'id_ecole',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }

    public function classes()
    {
        return $this->hasMany(Classe::class, 'id_filiere', 'id_filiere');
    }
}
