<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Matiere extends Model
{
    use BelongsToSchool;
    protected $table = 'matiere';
    protected $primaryKey = 'id_matiere';
    public $timestamps = false;

    protected $fillable = [
        'nom_matiere',
        'id_ecole',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }

    public function ordres()
    {
        return $this->hasMany(MatiereOrdre::class, 'id_matiere', 'id_matiere');
    }
}
