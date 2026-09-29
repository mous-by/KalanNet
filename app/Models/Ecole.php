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
        'id_revendeur',
        'id_pays',
        'nomComplexe',
        'cap',
        'statut',
        'adresse',
        'telephone',
        'email',
        'academie',
        'notification_sms',
        'notification_email',
        'franco_arabe',
    ];

    protected $casts = [
        'franco_arabe' => 'boolean',
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

    public function revendeur()
    {
        return $this->belongsTo(Revendeur::class, 'id_revendeur');
    }

    public function pays()
    {
        return $this->belongsTo(Pays::class, 'id_pays');
    }

    public function formatMontant(float $montant): string
    {
        return \App\Support\Devise::format($montant, $this);
    }

    // Une école publique ne pratique ni réduction ni subvention : seules les
    // coopératives s'y appliquent.
    public function estPublique(): bool
    {
        return strtolower(trim((string) $this->statut)) === 'public';
    }
}
