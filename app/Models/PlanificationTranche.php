<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanificationTranche extends Model
{
    public const MIN_TRANCHES = 2;
    public const MAX_TRANCHES = 6;

    protected $table = 'planification_tranches';
    public $timestamps = false;

    protected $fillable = [
        'id_planification',
        'numero',
        'libelle',
        'montant',
        'date_limite',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_limite' => 'date',
    ];

    public function planification()
    {
        return $this->belongsTo(Planification::class, 'id_planification', 'id_planification');
    }

    public static function libelleFor(int $numero): string
    {
        return $numero === 1 ? '1ère tranche' : $numero . 'e tranche';
    }
}
