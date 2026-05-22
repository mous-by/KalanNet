<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigneSalaire extends Model
{
    protected $table = 'ligne_salaire';
    protected $primaryKey = 'id_ligne_paiement';
    public $timestamps = false;

    protected $fillable = [
        'id_salaire',
        'montant_verse',
        'date_paiement',
    ];

    protected $casts = [
        'date_paiement' => 'date',
    ];

    public function salaire()
    {
        return $this->belongsTo(Salaire::class, 'id_salaire', 'id_salaire');
    }
}
