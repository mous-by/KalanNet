<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Proposition de passage d'une classe pour une année (voir App\Support\PropositionPassageDonnees). */
class PropositionPassage extends Model
{
    use BelongsToSchool;

    protected $table = 'propositions_passage';

    protected $fillable = ['id_ecole', 'id_annee', 'id_classe', 'id_cap', 'statut', 'modele', 'directeur', 'renseignements', 'lignes', 'lignes_envoyees', 'envoye_at', 'envoye_par', 'valide_at', 'valide_par'];

    protected $casts = ['renseignements' => 'array', 'lignes' => 'array', 'lignes_envoyees' => 'array', 'envoye_at' => 'datetime', 'valide_at' => 'datetime'];

    public function remise(): bool
    {
        return $this->statut !== 'brouillon';
    }

    public function validee(): bool
    {
        return $this->statut === 'validee';
    }

    /** L'école a modifié sa fiche depuis qu'elle l'a remise. */
    public function aModificationsNonEnvoyees(): bool
    {
        if (!$this->remise()) {
            return false;
        }
        $sansCap = fn (array $lignes) => array_map(fn ($l) => array_diff_key($l, ['decision_cap' => 1, 'observation_cap' => 1]), $lignes);

        return $sansCap($this->lignes ?? []) != $sansCap($this->lignes_envoyees ?? []);
    }
}
