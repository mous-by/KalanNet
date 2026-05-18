<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatiereOrdre extends Model
{
    protected $table = 'matiere_ordre';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_matiere',
        'ordre_enseignement',
    ];

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'id_matiere', 'id_matiere');
    }
}
