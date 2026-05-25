<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model
{
    protected $table = 'abonnements';

    protected $fillable = [
        'ecole_id',
        'offre_id',
        'statut',
        'debut_at',
        'fin_at',
        'dernier_paiement_id',
    ];

    protected $casts = [
        'debut_at' => 'datetime',
        'fin_at' => 'datetime',
    ];

    public function offre()
    {
        return $this->belongsTo(AbonnementOffre::class, 'offre_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id', 'idEcole');
    }

    public function paiements()
    {
        return $this->hasMany(AbonnementPaiement::class, 'abonnement_id');
    }
}
