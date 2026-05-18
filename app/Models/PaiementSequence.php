<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaiementSequence extends Model
{
    protected $table = 'paiement_sequences';

    protected $fillable = [
        'ecole_id',
        'type',
        'dernier_numero',
    ];
}
