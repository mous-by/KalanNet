<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Caisse extends Model
{
    use BelongsToSchool;
    protected $table = 'caisse';
    protected $primaryKey = 'id_caisse';
    public $timestamps = false;

    protected $fillable = [
        'libelle',
        'created_at',
        'montant_initial',
        'montant_net',
        'id_ecole',
        'status',
        'reference',
        'updated_at',
    ];

    protected $casts = [
        'montant_initial' => 'decimal:2',
        'montant_net' => 'decimal:2',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'id_caisse', 'id_caisse');
    }
}
