<?php

namespace Albaroody\Staging\Models;

use Illuminate\Database\Eloquent\Model;

class StagingEntry extends Model
{
    protected $fillable = [
        'id',
        'model',
        'model_id', // Reserved for future use
        'parent_staging_id',
        'parent_model',
        'relationship_type',
        'sort_order',
        'data',
        'expires_at', // Reserved for future use
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
    ];

    public $incrementing = false; // because id is UUID

    protected $keyType = 'string';

    /**
     * Get the temp_id attribute (alias for id)
     */
    public function getTempIdAttribute(): string
    {
        return $this->id;
    }

    /**
     * Get the parent_temp_id attribute (alias for parent_staging_id)
     */
    public function getParentTempIdAttribute(): ?string
    {
        return $this->parent_staging_id;
    }

    /**
     * Scope a query to only include children of a specific parent
     */
    public function scopeChildrenOfParent($query, string $parentStagingId, string $parentModel)
    {
        return $query->where('parent_staging_id', $parentStagingId)
            ->where('parent_model', $parentModel);
    }

    /**
     * Scope a query to filter by relationship type
     */
    public function scopeByRelationshipType($query, string $relationshipType)
    {
        return $query->where('relationship_type', $relationshipType);
    }

    /**
     * Scope a query to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
