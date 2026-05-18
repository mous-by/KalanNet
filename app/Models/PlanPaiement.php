<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPaiement extends Model
{
    protected $table = 'plans_paiement';

    protected $fillable = [
        'eleve_id',
        'ecole_id',
        'classe_id',
        'annee_scolaire_id',
        'mode_paiement',
        'statut_paiement',
        'montant_total',
        'reduction',
        'montant_final',
        'payeur_type',
        'payeur_libelle',
        'details_frais',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'reduction' => 'decimal:2',
        'montant_final' => 'decimal:2',
        'details_frais' => 'array',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class, 'eleve_id', 'id_eleve');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id', 'idEcole');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id', 'id_classe');
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id', 'id_anneeScolaire');
    }

    public function echeances()
    {
        return $this->hasMany(EcheancePaiement::class, 'plan_paiement_id');
    }
}
