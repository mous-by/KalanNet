<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Paiement extends Model
{
    use BelongsToSchool;
    protected $table = 'paiement';
    protected $primaryKey = 'id_paiement';
    public $timestamps = false;

    protected $fillable = [
        'montant',
        'montant_paye',
        'date_paiement',
        'mode_reglement',
        'statut',
        'annule_at',
        'annule_par',
        'motif_annulation',
        'motif',
        'id_classe',
        'id_annee',
        'id_trimestre',
        'reference',
        'idEcole',
        'id_eleve',
        'echeance_id',
        'encaissement_id',
        'parent',
        'nom_payeur',
        'telephone',
        'id_utilisateur',
        'id_caisse',
        'numero_recu',
        'id_planification',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'date_paiement' => 'datetime',
        'annule_at' => 'datetime',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'idEcole', 'idEcole');
    }

    public function eleve()
    {
        return $this->belongsTo(Eleve::class, 'id_eleve', 'id_eleve');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'id_caisse', 'id_caisse');
    }

    public function echeance()
    {
        return $this->belongsTo(EcheancePaiement::class, 'echeance_id');
    }

    public function encaissement()
    {
        return $this->belongsTo(Encaissement::class, 'encaissement_id', 'id_encaissement');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'id_utilisateur', 'idUtilisateur');
    }
}
