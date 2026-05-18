<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Emargement extends Model
{
    use BelongsToSchool;
    protected $table = 'emargement';
    protected $primaryKey = 'id_emargement';
    public $timestamps = false;

    protected $fillable = [
        'id_enseignant',
        'id_classe',
        'id_matiere',
        'chapitre',
        'id_lecon',
        'nombre_heure',
        'id_trimestre',
        'id_anneeScolaire',
        'date_emargement',
        'id_ecole',
        'valide',
    ];

    protected $casts = [
        'date_emargement' => 'datetime',
        'valide' => 'boolean',
    ];

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignant', 'id_enseignant');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'id_matiere', 'id_matiere');
    }

    public function trimestre()
    {
        return $this->belongsTo(Trimestre::class, 'id_trimestre', 'id_trimestre');
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'id_anneeScolaire', 'id_anneeScolaire');
    }

    public function lecon()
    {
        return $this->belongsTo(ProgrammeLecon::class, 'id_lecon', 'id_lecon');
    }
}
