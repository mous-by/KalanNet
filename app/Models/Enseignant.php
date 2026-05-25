<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Enseignant extends Model
{
    use BelongsToSchool;
    protected $table = 'enseignants';
    protected $primaryKey = 'id_enseignant';
    public $timestamps = false;

    protected $fillable = [
        'nom_prenom_enseignant',
        'genre_enseignant',
        'email_enseignant',
        'telephone_enseignant',
        'date_naissance_enseignant',
        'lieu_naissance_enseignant',
        'diplome_enseignant',
        'salaire_enseignant',
        'salaire_mois_mode',
        'type_contrat_enseignant',
        'matricule',
        'avatar_enseignant',
        'id_emploi_du_temps',
        'duree_contrat',
        'nombre_heure',
        'prix_heure',
        'pwd',
        'is_deleted',
        'deleted_at',
        'id_ecole',
        'statut_matrimonial',
        'nombre_enfants',
        'pere_nom_prenom',
        'mere_nom_prenom',
        'specialite',
        'service_employeur',
        'anciennete_annees',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }

    public function matieres()
    {
        return $this->hasMany(Emargement::class, 'id_enseignant', 'id_enseignant');
    }

    public function emargements()
    {
        return $this->hasMany(Emargement::class, 'id_enseignant', 'id_enseignant');
    }

    public function presences()
    {
        return $this->hasMany(Presence::class, 'id_enseignant', 'id_enseignant');
    }

    public function salaires()
    {
        return $this->hasMany(Salaire::class, 'id_enseignant', 'id_enseignant');
    }
}
