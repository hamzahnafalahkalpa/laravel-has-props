<?php

namespace Hanafalah\LaravelHasProps\Concerns;

use Stancl\VirtualColumn\VirtualColumn;

trait HasProps
{
    use VirtualColumn;
    use PropAttribute {
        PropAttribute::getCustomColumns insteadof VirtualColumn;
    }
    public $using_props = true;
    protected static string $__prop_name    = 'props';
    public static bool $__prop_event_active = true;

    public static function bootHasProps()
    {
        static::addGlobalScope('with_prop', function ($query) {
            $model = $query->getModel();
            $model->mergeFillable(array_merge($model->getFillable(), ['props']));
            // if (!$query->getQuery()->columns) {
            //     $query->select($query->getModel()->getDataColumn());
            // }else{
            //     $query->prop();
            // }
        });
    }

    public function initializeHasProps()
    {
        $this->mergeFillable([
            self::$__prop_name
        ]);
    }

    protected function getPropLists(): array{
        return [];
    }

    public function getPropsQuery(): array
    {
        return [];
    }

    /**
     * Get the name of the column that stores additional data.
     */
    public static function getDataColumn(): string
    {
        return self::$__prop_name;
    }

    /**
     * Scope a query to only include models where the given column key
     * exists in the props attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $column
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProp($builder, string $column = null): \Illuminate\Database\Eloquent\Builder
    {
        return $builder->addSelect($column ?? $this->getDataColumn());
    }

    /**
     * Scope a query to only include models where the given column key
     * exists in the props attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  string  $column
     * @param  mixed  $args
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereProp($builder, string $json_var, ...$args)
    {
        $lastArg = array_slice($args, -1)[0];
        if (is_callable($lastArg) && $lastArg instanceof \Closure) {
            return $builder->when(function ($query) use ($args, $lastArg) {
                $lastArg($query, ...$args);
            });
        } else {
            return $builder->whereJsonContains($this->getDataColumn() . '->' . $json_var, ...$args);
        }
    }

    /**
     * Get the props attribute.
     *
     * @return array
     */
    public function getProps()
    {
        return $this->decodeAttributes();
    }

    public function getPropsKey()
    {
        $fillable = $this->getFillable();
        $attributes = $this->getAttributes();
        if ($this->usesTimestamps()) $fillable = $this->mergeArray($fillable, ['created_at', 'updated_at']);
        $fillable = $this->mergeArray($fillable, ['deleted_at']);
        $diff = array_diff_key($attributes, array_flip($fillable));
        return  $diff == [] ? null : $diff;
    }

    /**
     * Set the props attribute.
     *
     * @return $this
     */
    public function setProps()
    {
        return $this->encodeAttributes();
    }

    protected function getAfterListeners(): array
    {
        if (!static::$__prop_event_active) return [];
        return [
            'retrieved' => [
                function () {
                    // Always decode after model retrieval
                    $this->dataEncoded = true;

                    $this->decodeVirtualColumn();
                },
            ],
            'saving' => [
                [$this, 'encodeAttributes'],
            ],
            'creating' => [
                [$this, 'encodeAttributes'],
            ],
            'updating' => [
                [$this, 'encodeAttributes'],
            ],
        ];
    }
}
