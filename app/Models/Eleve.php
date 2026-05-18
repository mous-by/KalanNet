<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Eleve extends Model
{
    use BelongsToSchool;
    protected $table = 'eleve';
    protected $primaryKey = 'id_eleve';
    public $timestamps = false;

    protected $fillable = [
        'date_naissance',
        'lieu_naiss',
        'adresse_eleve',
        'genre_eleve',
        'id_annee',
        'date_inscription',
        'image',
        'matricule',
        'id_classe',
        'cas_social',
        'mode_paiement',
        'statut_paiement',
        'id_ecole',
        'nom_eleve',
        'prenom_eleve',
        'etat_dossier',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'ligneparents_eleves', 'id_eleve', 'id_parent')
            ->withPivot(['informer', 'lien_parent']);
    }

    public function plansPaiement()
    {
        return $this->hasMany(PlanPaiement::class, 'eleve_id', 'id_eleve');
    }
}
