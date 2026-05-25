<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AppelEpreuve extends Model
{
    use BelongsToSchool;

    protected $table = 'controle_eleve';
    protected $primaryKey = 'id_controle_eleve';
    public $timestamps = false;

    protected $fillable = [
        'id_eleve',
        'id_classe',
        'id_matiere',
        'id_annee_scolaire',
        'id_trimestre',
        'id_ecole',
        'date',
        'libelle',
        'heure_debut',
        'heure_fin',
        'notifier_parent',
        'id_controle',
    ];

    protected $casts = [
        'date' => 'date',
        'notifier_parent' => 'boolean',
        'date_enregistrement' => 'datetime',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class, 'id_eleve', 'id_eleve');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'id_matiere', 'id_matiere');
    }

    public function annee()
    {
        return $this->belongsTo(AnneeScolaire::class, 'id_annee_scolaire', 'id_anneeScolaire');
    }

    public function trimestre()
    {
        return $this->belongsTo(Trimestre::class, 'id_trimestre', 'id_trimestre');
    }

    public function statutControle()
    {
        return $this->belongsTo(Controle::class, 'id_controle', 'id_controle');
    }
}
