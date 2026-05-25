<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbonnementPaiement extends Model
{
    protected $table = 'abonnement_paiements';

    protected $fillable = [
        'abonnement_id',
        'ecole_id',
        'offre_id',
        'fournisseur',
        'reference',
        'reference_fournisseur',
        'numero_payeur',
        'mode_paiement',
        'transaction_ref',
        'owner_note',
        'preuve_url',
        'montant',
        'devise',
        'statut',
        'checkout_url',
        'payload',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'paye_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'payload' => 'array',
        'reviewed_at' => 'datetime',
        'paye_at' => 'datetime',
    ];

    public function abonnement()
    {
        return $this->belongsTo(Abonnement::class, 'abonnement_id');
    }

    public function offre()
    {
        return $this->belongsTo(AbonnementOffre::class, 'offre_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id', 'idEcole');
    }
}
