<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banque extends Model
{
    protected $table = 'banques';
    protected $primaryKey = 'id_banques';
    public $timestamps = false;

    protected $fillable = [
        'numero_compte',
        'nom_banque',
        'solde',
        'id_ecole',
        'date_creation',
        'updated_at',
    ];

    protected $casts = [
        'solde' => 'decimal:2',
        'date_creation' => 'date',
        'updated_at' => 'date',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }

    public function versements()
    {
        return $this->hasMany(Versement::class, 'id_banque', 'id_banques');
    }

    public function retraits()
    {
        return $this->hasMany(Retrait::class, 'id_banque', 'id_banques');
    }
}
