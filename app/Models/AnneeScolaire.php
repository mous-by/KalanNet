<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class AnneeScolaire extends Model
{
    use BelongsToSchool;
    protected $table = 'anneescolaire';
    protected $primaryKey = 'id_anneeScolaire';
    public $timestamps = false;

    protected $fillable = [
        'annee',
        'date_debut',
        'date_fin',
        'id_ecole',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }
}
