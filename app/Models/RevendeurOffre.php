<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevendeurOffre extends Model
{
    protected $table = 'revendeur_offres';

    protected $fillable = [
        'id_revendeur',
        'id_offre',
        'montant_revente',
        'actif',
    ];

    protected $casts = [
        'montant_revente' => 'decimal:2',
        'actif' => 'boolean',
    ];

    public function revendeur()
    {
        return $this->belongsTo(Revendeur::class, 'id_revendeur');
    }

    public function offre()
    {
        return $this->belongsTo(AbonnementOffre::class, 'id_offre');
    }
}
