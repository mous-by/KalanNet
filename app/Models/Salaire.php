<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salaire extends Model
{
    protected $table = 'salaire';
    protected $primaryKey = 'id_salaire';
    public $timestamps = false;

    protected $fillable = [
        'type_paiement',
        'reference',
        'montant_a_payer',
        'id_enseignant',
        'id_inscription',
        'created_at',
        'mois',
        'annee',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignant', 'id_enseignant');
    }

    public function lignes()
    {
        return $this->hasMany(LigneSalaire::class, 'id_salaire', 'id_salaire');
    }
}
