<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceMode extends Model
{
    public const DEFAULT_MESSAGE = "KalanNet est actuellement en maintenance. Merci de réessayer dans quelques instants.";

    protected $table = 'maintenance_mode';

    protected $fillable = [
        'actif',
        'message',
        'active_par',
        'active_at',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'active_at' => 'datetime',
    ];

    /**
     * Ligne unique (id=1) représentant l'état courant de la plateforme.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function activePar()
    {
        return $this->belongsTo(User::class, 'active_par', 'idUtilisateur');
    }
}
