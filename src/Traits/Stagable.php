<?php

namespace Albaroody\Staging\Traits;

use Albaroody\Staging\Services\StagingManager;

trait Stagable
{
    public function stage()
    {
        return StagingManager::stage($this);
    }

    public static function stageNew(array $attributes, $parentStagingId = null)
    {
        $model = new static($attributes);

        return StagingManager::stage($model, $parentStagingId);
    }

    public static function findStaged(string $stagingId)
    {
        return StagingManager::load($stagingId);
    }

    public function promoteFromStaging()
    {
        return StagingManager::promote($this);
    }
}
