<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Cahier de bord d'une école pour une année et un cycle (voir App\Support\CahierBordDonnees). */
class CahierBord extends Model
{
    use BelongsToSchool;

    protected $table = 'cahiers_bord';

    protected $fillable = ['id_ecole', 'id_annee', 'cycle', 'id_cap', 'id_academie', 'statut', 'donnees', 'donnees_envoyees', 'envoye_at', 'envoye_par'];

    protected $casts = ['donnees' => 'array', 'donnees_envoyees' => 'array', 'envoye_at' => 'datetime'];

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
