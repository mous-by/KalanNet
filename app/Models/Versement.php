<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Versement extends Model
{
    protected $table = 'versement';
    protected $primaryKey = 'id_versement';
    public $timestamps = false;

    protected $fillable = [
        'date_versement',
        'motif_versement',
        'montant_versement',
        'id_banque',
        'id_annee_scolaire',
        'idUtilisateur',
    ];

    protected $casts = [
        'date_versement' => 'date',
        'montant_versement' => 'decimal:2',
    ];

    public function banque()
    {
        return $this->belongsTo(Banque::class, 'id_banque', 'id_banques');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'idUtilisateur', 'idUtilisateur');
    }
}
