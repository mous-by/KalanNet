<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbonnementOffre extends Model
{
    protected $table = 'abonnement_offres';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'montant',
        'devise',
        'duree_jours',
        'type_ecole_cible',
        'actif',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'actif' => 'boolean',
    ];
}
