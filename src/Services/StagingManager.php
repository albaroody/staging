<?php

namespace Albaroody\Staging\Services;

use Albaroody\Staging\Models\StagingEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StagingManager
{
    public static function stage(Model $model, $parentStagingId = null)
    {
        $stagingId = $model->staging_id ?? (string) Str::uuid();

        StagingEntry::updateOrCreate(
            ['id' => $stagingId],
            [
                'model' => get_class($model),
                'parent_staging_id' => $parentStagingId,
                'data' => $model->toArray(),
            ]
        );

        return $stagingId;
    }

    public static function load(string $stagingId): Model
    {
        $stagingEntry = StagingEntry::findOrFail($stagingId);

        $modelClass = $stagingEntry->model;
        $data = $stagingEntry->data;

        $model = (new $modelClass())->newInstance($data, [], true);
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
}
