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
        'actif',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'actif' => 'boolean',
    ];
}
