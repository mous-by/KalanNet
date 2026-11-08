<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Rapport de rentrée ou de fin d'année d'un établissement du secondaire (voir App\Support\RapportSecondaireDonnees). */
class RapportSecondaire extends Model
{
    use BelongsToSchool;

    protected $table = 'rapports_secondaire';

    protected $fillable = ['id_ecole', 'id_annee', 'type', 'id_academie', 'statut', 'donnees', 'donnees_envoyees', 'envoye_at', 'envoye_par'];

    protected $casts = ['donnees' => 'array', 'donnees_envoyees' => 'array', 'envoye_at' => 'datetime'];

    public function remis(): bool
    {
        return $this->statut !== 'brouillon';
    }

    /** L'établissement a modifié son rapport depuis qu'il l'a remis. */
    public function aModificationsNonEnvoyees(): bool
    {
        return $this->remis() && $this->donnees != $this->donnees_envoyees;
    }
}
