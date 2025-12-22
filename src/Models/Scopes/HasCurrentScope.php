<?php

namespace Hanafalah\LaravelHasProps\Models\Scopes;

use Illuminate\Database\Eloquent\{
    Builder,
    Model,
    Scope
};

class HasCurrentScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (isset($model->current_checking) && $model->current_checking && in_array($model->getCurrentName(), $model->getFillable())) {
            $builder->limit(1)->orderBy($model->getCurrentName(), 'desc');
        }
    }
}
