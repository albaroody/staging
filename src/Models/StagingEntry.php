<?php

namespace Albaroody\Staging\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class StagingEntry extends Model
{
    use NodeTrait;
    protected $fillable = [
        'id',
        'model',
        'parent_id',
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
