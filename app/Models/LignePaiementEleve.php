<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LignePaiementEleve extends Model
{
    protected $table = 'ligne_paiement_eleve';
    protected $primaryKey = 'idligne_paiement_eleve';
    public $timestamps = false;

    protected $fillable = [
        'id_classe',
        'id_annee',
        'id_paiement',
        'id_eleve',
        'id_trimestre',
        'idEcole',
    ];
}
