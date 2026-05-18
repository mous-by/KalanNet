<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcheancePaiement extends Model
{
    protected $table = 'echeances_paiement';

    protected $fillable = [
        'plan_paiement_id',
        'libelle',
        'montant_prevu',
        'date_limite',
        'statut',
    ];

    protected $casts = [
        'montant_prevu' => 'decimal:2',
        'date_limite' => 'date',
    ];

    public function planPaiement()
    {
        return $this->belongsTo(PlanPaiement::class, 'plan_paiement_id');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'echeance_id', 'id');
    }
}
