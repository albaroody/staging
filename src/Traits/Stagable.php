<?php

namespace Albaroody\Staging\Traits;

use Albaroody\Staging\Services\StagingManager;
use Illuminate\Support\Collection;

trait Stagable
{
    /**
     * Boot the trait and set up automatic child saving
     */
    protected static function bootStagable()
    {
        static::created(function ($model) {
            // Check if model has any hasMany relationships with staged children
            $relationships = static::getHasManyRelationships();

            foreach ($relationships as $relationshipName => $config) {
                $tempIdsKey = $relationshipName.'_staging_ids';
                $stagingIdKey = '_staging_id';

                // Check if we have staged children IDs in the model attributes or request
                $stagingIds = null;
                if (isset($model->attributes[$tempIdsKey])) {
                    $stagingIds = $model->attributes[$tempIdsKey];
                } elseif (request()->has($tempIdsKey)) {
                    $stagingIds = request()->input($tempIdsKey);
                }

                if ($stagingIds && is_array($stagingIds) && count($stagingIds) > 0) {
                    $childModel = $config['model'];
                    $foreignKey = $config['foreign_key'];

                    // Get parent staging ID from model attributes or request
                    $parentStagingId = null;
                    if (isset($model->attributes[$stagingIdKey])) {
                        $parentStagingId = $model->attributes[$stagingIdKey];
                    } elseif (request()->has($stagingIdKey)) {
                        $parentStagingId = request()->input($stagingIdKey);
                    }

                    if ($parentStagingId) {
                        $childModel::saveStagedChildren(
                            $model->id,
                            $parentStagingId,
                            static::class,
                            $foreignKey
                        );
                    }
                }
            }
        });
    }

    /**
     * Define hasMany relationships that support staging
     * Override in model to define relationships
     */
    protected static function getHasManyRelationships(): array
    {
        // Default implementation - can be overridden in models
        return [];
    }

    public function stage(
        ?string $parentStagingId = null,
        ?string $parentModel = null,
        ?string $relationshipType = null
    ) {
        return StagingManager::stage($this, $parentStagingId, $parentModel, $relationshipType);
    }

    public static function stageNew(
        array $attributes,
        ?string $parentStagingId = null,
        ?string $parentModel = null,
        ?string $relationshipType = null
    ) {
        $model = new static($attributes);

        return StagingManager::stage($model, $parentStagingId, $parentModel, $relationshipType);
    }

    public static function findStaged(string $stagingId)
    {
        return StagingManager::load($stagingId);
    }

    public function promoteFromStaging()
    {
        return StagingManager::promote($this);
    }

    /**
     * Stage multiple new objects at once
     *
     * @param  array  $items  Array of data arrays, one per object to stage
     * @param  string|null  $parentStagingId  Optional parent staging ID
     * @param  string|null  $parentModel  Optional parent model class
     * @param  string|null  $relationshipType  Optional relationship type
     * @return array Array of staging IDs
     */
    public static function stageMany(
        array $items,
        ?string $parentStagingId = null,
        ?string $parentModel = null,
        ?string $relationshipType = null
    ): array {
        // Add model class to each item for StagingManager::stageMany
        $itemsWithModel = array_map(function ($item) {
            $item['_model_class'] = static::class;

            return $item;
        }, $items);

        return StagingManager::stageMany($itemsWithModel, $parentStagingId, $parentModel, $relationshipType);
    }

    /**
     * Link staged children to a staged parent
     *
     * @param  array|string  $childStagingIds  Single staging ID or array of staging IDs
     * @param  string  $parentStagingId  The parent's staging ID
     * @param  string  $parentModel  The parent model class
     */
    public static function linkStagedToParent($childStagingIds, string $parentStagingId, string $parentModel): void
    {
        StagingManager::linkStagedToParent($childStagingIds, $parentStagingId, $parentModel);
    }

    /**
     * Find all staged children for a given parent
     *
     * @param  string  $parentStagingId  The parent's staging ID
     * @param  string  $parentModel  The parent model class
     * @return Collection Collection of staged data
     */
    public static function findStagedChildren(string $parentStagingId, string $parentModel): Collection
    {
        return StagingManager::findStagedChildren($parentStagingId, $parentModel);
    }

    /**
     * Save all staged children when parent is saved
     *
     * @param  int  $parentId  The saved parent's ID
     * @param  string  $parentStagingId  The parent's staging ID (before saving)
     * @param  string  $parentModel  The parent model class
     * @param  string  $foreignKey  The foreign key column name in child table
     * @return array Array of created child IDs
     */
    public static function saveStagedChildren(
        int $parentId,
        string $parentStagingId,
        string $parentModel,
        string $foreignKey
    ): array {
        return StagingManager::saveStagedChildren(
            static::class,
            $parentId,
            $parentStagingId,
            $parentModel,
            $foreignKey
        );
    }

    /**
     * Get a collection of staged objects as if they were Eloquent models
     * Useful for displaying in forms/tables before saving
     *
     * @param  array  $stagingIds  Array of staging IDs
     * @return Collection Collection of model-like objects
     */
    public static function getStagedCollection(array $stagingIds): Collection
    {
        return StagingManager::getStagedCollection($stagingIds, static::class);
    }
}
