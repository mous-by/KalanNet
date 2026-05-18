<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploiDuTemps extends Model
{
    protected $table = 'emploi_du_temps';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_classe',
        'id_matiere',
        'id_enseignant',
        'id_annee_scolaire',
        'jour',
        'heure_debut',
        'heure_fin'
    ];

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'id_matiere', 'id_matiere');
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignant', 'id_enseignant');
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'id_annee_scolaire', 'id_anneeScolaire');
    }
}
