<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Rapport de rentrée ou de fin d'année d'un lycée, remis à l'académie (voir App\Services\Rapports\CanevasEsg). */
class RapportEsg extends Model
{
    use BelongsToSchool;

    protected $table = 'rapports_esg';

    protected $fillable = ['id_ecole', 'id_annee', 'type', 'id_academie', 'statut', 'fichier', 'fichier_nom', 'envoye_at', 'envoye_par'];

    protected $casts = ['envoye_at' => 'datetime'];
}
