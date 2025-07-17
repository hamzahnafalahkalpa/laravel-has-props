<?php

namespace Hanafalah\LaravelHasProps\Concerns;

trait PropAttribute
{
    public static function getCustomColumns(): array
    {
        $model = new static;
        $non_props = $model->getFillable();
        return array_merge(
            $non_props,
            $model->usesTimestamps() ? ['created_at', 'updated_at'] : [],
            method_exists(static::class, 'bootSoftDeletes') ? ['deleted_at'] : []
        );
    }
}
