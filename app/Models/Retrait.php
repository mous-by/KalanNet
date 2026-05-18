<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retrait extends Model
{
    protected $table = 'retrait';
    protected $primaryKey = 'id_retrait';
    public $timestamps = false;

    protected $fillable = [
        'id_banque',
        'date_retrait',
        'montant_retrait',
        'motif_retrait',
        'id_annee_scolaire',
        'idUtilisateur',
        'created_at',
        'valide',
    ];

    protected $casts = [
        'date_retrait' => 'date',
        'created_at' => 'datetime',
        'montant_retrait' => 'decimal:2',
        'valide' => 'boolean',
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
