<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeconPresence extends Model
{
    protected $table = 'lecons_presence';
    protected $primaryKey = 'id_lecon_presence';
    public $timestamps = false;

    protected $fillable = [
        'id_presence',
        'titre',
        'nombre_heure',
        'progression',
    ];

    public function presence()
    {
        return $this->belongsTo(Presence::class, 'id_presence', 'id_presence');
    }
}
