<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Rapport succinct de rentrée d'une école pour une année (voir App\Support\RapportSuccinctDonnees). */
class RapportSuccinct extends Model
{
    use BelongsToSchool;

    protected $table = 'rapports_succincts';

    protected $fillable = ['id_ecole', 'id_annee', 'portee', 'id_cap', 'id_academie', 'statut', 'donnees', 'donnees_envoyees', 'envoye_at', 'envoye_par'];

    protected $casts = ['donnees' => 'array', 'donnees_envoyees' => 'array', 'envoye_at' => 'datetime'];

    public function annee()
    {
        return $this->belongsTo(AnneeScolaire::class, 'id_annee', 'id_anneeScolaire');
    }

    public function remis(): bool
    {
        return $this->statut !== 'brouillon';
    }

    /** L'école a modifié sa fiche depuis qu'elle l'a remise. */
    public function aModificationsNonEnvoyees(): bool
    {
        return $this->remis() && $this->donnees != $this->donnees_envoyees;
    }
}
