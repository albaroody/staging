<?php

namespace Albaroody\Staging\Services;

use Albaroody\Staging\Models\StagingEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StagingManager
{
    public static function stage(
        Model $model,
        $parentStagingId = null,
        ?string $parentModel = null,
        ?string $relationshipType = null
    ) {
        $stagingId = $model->staging_id ?? (string) Str::uuid();

        // Extract sort_order from data if present
        $data = $model->toArray();
        $sortOrder = 0;
        if (isset($data['_sort_order'])) {
            $sortOrder = $data['_sort_order'];
            unset($data['_sort_order']);
        }

        StagingEntry::updateOrCreate(
            ['id' => $stagingId],
            [
                'model' => get_class($model),
                'parent_staging_id' => $parentStagingId,
                'parent_model' => $parentModel,
                'relationship_type' => $relationshipType,
                'sort_order' => $sortOrder,
                'data' => $data,
            ]
        );

        // Set staging_id on the model so promote() can find it
        $model->staging_id = $stagingId;

        return $stagingId;
    }

    public static function load(string $stagingId): Model
    {
        $stagingEntry = StagingEntry::findOrFail($stagingId);

        $modelClass = $stagingEntry->model;
        $data = $stagingEntry->data;

        $model = (new $modelClass)->newInstance($data, [], true);
        $model->staging_id = $stagingEntry->id;

        return $model;
    }

    public static function promote(Model $model)
    {
        $realModel = $model->replicate();
        unset($realModel->staging_id);        // Just in case, to be extra clean
        $realModel->save();

        StagingEntry::where('id', $model->staging_id)->delete();

        return $realModel;
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
        $stagingIds = [];

        foreach ($items as $index => $itemData) {
            // Extract model class and remove from data
            $modelClass = $itemData['_model_class'] ?? null;
            if (! $modelClass) {
                throw new \InvalidArgumentException('Model class must be provided in _model_class key');
            }
            unset($itemData['_model_class']);

            // Extract sort_order before creating model (it might not be fillable)
            $sortOrder = $itemData['_sort_order'] ?? $index;
            unset($itemData['_sort_order']);

            $model = new $modelClass($itemData);
            // Set sort_order as attribute so it's available in toArray()
            $model->setAttribute('_sort_order', $sortOrder);

            $stagingId = static::stage($model, $parentStagingId, $parentModel, $relationshipType);
            $stagingIds[] = $stagingId;
        }

        return $stagingIds;
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
        $childStagingIds = is_array($childStagingIds) ? $childStagingIds : [$childStagingIds];

        StagingEntry::whereIn('id', $childStagingIds)
            ->update([
                'parent_staging_id' => $parentStagingId,
                'parent_model' => $parentModel,
                'relationship_type' => 'hasMany',
            ]);
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
        return StagingEntry::where('parent_staging_id', $parentStagingId)
            ->where('parent_model', $parentModel)
            ->where('relationship_type', 'hasMany')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($entry) {
                return [
                    'staging_id' => $entry->id,
                    'temp_id' => $entry->id, // Alias for compatibility
                    'data' => $entry->data,
                    'sort_order' => $entry->sort_order,
                ];
            });
    }

    /**
     * Save all staged children when parent is saved
     *
     * @param  string  $childModelClass  The child model class
     * @param  int  $parentId  The saved parent's ID
     * @param  string  $parentStagingId  The parent's staging ID (before saving)
     * @param  string  $parentModel  The parent model class
     * @param  string  $foreignKey  The foreign key column name in child table
     * @return array Array of created child IDs
     */
    public static function saveStagedChildren(
        string $childModelClass,
        int $parentId,
        string $parentStagingId,
        string $parentModel,
        string $foreignKey
    ): array {
        $stagedChildren = static::findStagedChildren($parentStagingId, $parentModel);
        $createdIds = [];

        foreach ($stagedChildren as $stagedChild) {
            $data = $stagedChild['data'];
            $data[$foreignKey] = $parentId; // Set the foreign key

            $child = (new $childModelClass)->create($data);
            $createdIds[] = $child->id;

            // Delete the staging entry
            StagingEntry::where('id', $stagedChild['staging_id'])->delete();
        }

        return $createdIds;
    }

    /**
     * Get a collection of staged objects as if they were Eloquent models
     * Useful for displaying in forms/tables before saving
     *
     * @param  array  $stagingIds  Array of staging IDs
     * @param  string  $modelClass  The model class
     * @return Collection Collection of model-like objects
     */
    public static function getStagedCollection(array $stagingIds, string $modelClass): Collection
    {
        return StagingEntry::whereIn('id', $stagingIds)
            ->where('model', $modelClass)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($entry) {
                $data = $entry->data;
                $data['staging_id'] = $entry->id;
                $data['temp_id'] = $entry->id; // Alias for compatibility
                $data['is_staged'] = true;

                return (object) $data; // Return as object to mimic Eloquent model
            });
    }
}
