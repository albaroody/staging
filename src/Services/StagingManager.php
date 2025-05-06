<?php

namespace Albaroody\Staging\Services;

use Albaroody\Staging\Models\StagingEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StagingManager
{
    /**
     * Stages a model by saving its data to the staging table.
     *
     * @param Model $model The model to stage.
     * @param string|null $parentStagingId The staging ID of the parent model, if any.
     * @return string The staging ID of the staged model.
     */

    public static function stage(Model $model, $parentStagingId = null)
    {
        $stagingId = $model->staging_id ?? (string) Str::uuid();

        $stagedInstance = StagingEntry::updateOrCreate(
            ['id' => $stagingId],
            [
                'id' => $stagingId,
                'model' => get_class($model),
                'parent_id' => $parentStagingId,
                'data' => $model->toArray(),
            ]
        );

        if($parentStagingId != null && $stagedInstance){
            $parent = StagingEntry::findOrFail($parentStagingId);
            $stagedInstance->parent()->associate($parent)->save();
        
        }

        StagingEntry::fixTree(); // rebuilds the _lft and _rgt values
        
        return $stagingId;
    }
    /**
     * Loads a staged model from the staging table.
     *
     * @param string $stagingId The ID of the staged model.
     * @return Model The loaded model.
     */

    public static function load(string $stagingId): Model
    {
        $stagingEntry = StagingEntry::findOrFail($stagingId);

        $modelClass = $stagingEntry->model;
        $data = $stagingEntry->data;

        $model = (new $modelClass())->newInstance($data, [], true);
        $model->staging_id = $stagingEntry->id;

        return $model;
    }
    /**
     * Promotes a staged model and all its children to the main database and removes them from staging.
     * This method is typically used for models that have a hierarchical structure.
     *
     * @param Model $model The staged model to promote along with its children.
     * @return void
     */

    public static function promoteAll(Model $model)
    {
        if ($model->staging_id == null) {
            return;
        }

        $stagingEntry = StagingEntry::findOrFail($model->staging_id);

        if ($stagingEntry->parent_id !== null) {
            return;
        }
       
        $children = $stagingEntry->descendants()->get();
       
        foreach($children as $child){
            $child = self::load($child->id);
            self::promote($child);
        }
        if ($stagingEntry != null){
            $modelEntry = self::load($stagingEntry->id);
            self::promote($modelEntry);
        }

    }

    /**
     * Promotes a staged model to the main database and removes it from staging.
     *
     * @param Model $model The staged model to promote.
     * @return Model The promoted model.
     */

    public static function promote(Model $model)
    {
        $staging_id = $model->staging_id;

        if ($staging_id == null) {
            return;
        }

        unset($model->staging_id);
        $model->save();

        StagingEntry::where('id', $staging_id)->delete();

        return $model;
    }
}
