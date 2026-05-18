<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraisScolaire extends Model
{
    protected $table = 'frais_scolaires';

    protected $fillable = [
        'ecole_id',
        'classe_id',
        'annee_scolaire_id',
        'type_frais',
        'montant',
        'obligatoire',
        'actif',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'obligatoire' => 'boolean',
        'actif' => 'boolean',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id', 'idEcole');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id', 'id_classe');
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id', 'id_anneeScolaire');
    }
}
