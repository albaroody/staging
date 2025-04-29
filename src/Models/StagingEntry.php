<?php

namespace Albaroody\Staging\Models;

use Illuminate\Database\Eloquent\Model;

class StagingEntry extends Model
{
    protected $fillable = [
        'id',
        'model',
        'parent_staging_id',
        'data',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
    ];

    public $incrementing = false; // because id is UUID

    protected $keyType = 'string';
}
