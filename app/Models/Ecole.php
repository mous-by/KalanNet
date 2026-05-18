<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Ecole extends Model
{
    use BelongsToSchool;
    protected $table = 'ecole';
    protected $primaryKey = 'idEcole';

    protected $fillable = [
        'nomEcole',
        'typeEcole',
        'logoEcole',
        'nomFondamental',
        'nomLycee',
        'nomProfessionnel',
        'id_academie',
        'id_cap',
        'nomComplexe',
        'cap',
        'statut',
        'adresse',
        'telephone',
        'email',
        'academie',
        'notification_sms',
    ];

    public function utilisateurs()
    {
        return $this->hasMany(User::class, 'idEcole', 'idEcole');
    }

    public function academieRef()
    {
        return $this->belongsTo(Academie::class, 'id_academie', 'id_academie');
    }

    public function capRef()
    {
        return $this->belongsTo(Cap::class, 'id_cap', 'id_cap');
    }
}
