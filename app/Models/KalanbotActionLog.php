<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KalanbotActionLog extends Model
{
    protected $table = 'kalanbot_actions_log';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'id_ecole',
        'module',
        'tool_name',
        'arguments',
        'status',
        'message',
        'result_data',
        'created_at',
    ];

    protected $casts = [
        'arguments' => 'array',
        'result_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'idUtilisateur');
    }
}
