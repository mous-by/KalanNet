<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReductionPaiementConfig extends Model
{
    protected $table = 'reduction_paiement_configs';

    protected $fillable = [
        'ecole_id',
        'annee_scolaire_id',
        'statut_paiement',
        'type_reduction',
        'valeur',
        'payeur_libelle',
        'actif',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'actif' => 'boolean',
    ];
}
