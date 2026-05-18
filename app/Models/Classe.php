<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Classe extends Model
{
    use BelongsToSchool;
    protected $table = 'classe';
    protected $primaryKey = 'id_classe';
    public $timestamps = false;

    protected $fillable = [
        'nom_classe',
        'ordreEnseignement',
        'idEcole',
        'id_classe_officielle',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'idEcole', 'idEcole');
    }

    public function eleves()
    {
        return $this->hasMany(Eleve::class, 'id_classe', 'id_classe');
    }

    public function ligneClasses()
    {
        return $this->hasMany(LigneClasse::class, 'id_classe', 'id_classe');
    }

    public function classeOfficielle()
    {
        return $this->belongsTo(ClasseOfficielle::class, 'id_classe_officielle', 'id_classe_officielle');
    }
}
