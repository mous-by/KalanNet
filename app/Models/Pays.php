<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pays extends Model
{
    protected $table = 'pays';

    protected $fillable = [
        'nom',
        'code_iso',
        'indicatif_telephone',
        'telephone_longueur',
        'telephone_premier_chiffre_min',
        'devise_code',
        'devise_symbole',
        'devise_decimales',
        'actif',
        'niveau_examen_intermediaire',
        'nom_examen_intermediaire',
        'niveau_examen_final',
        'nom_examen_final',
    ];

    protected $casts = [
        'telephone_longueur' => 'integer',
        'devise_decimales' => 'integer',
        'actif' => 'boolean',
        'niveau_examen_intermediaire' => 'integer',
        'niveau_examen_final' => 'integer',
    ];

    public function ecoles()
    {
        return $this->hasMany(Ecole::class, 'id_pays');
    }

    public function formatMontant(float $montant): string
    {
        $formatte = number_format($montant, $this->devise_decimales, ',', ' ');

        return "{$formatte} {$this->devise_symbole}";
    }
}
