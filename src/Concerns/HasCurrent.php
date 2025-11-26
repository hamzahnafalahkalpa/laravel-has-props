<?php

namespace Hanafalah\LaravelHasProps\Concerns;

trait HasCurrent
{
    public bool $current_checking      = true;

    protected string $__current_name = 'current';
    protected int $__is_current      = 1;
    protected int $__is_not_current  = 0;
    // /**
    //  * Initialize the trait.
    //  *
    //  * @return void
    //  */
    // public function initializeHasCurrent()
    // {
    //     $this->mergeFillable([
    //         $this->getCurrentName()
    //     ]);
    // }

    public function getConditions(): array
    {
        return $this->current_conditions ?? [];
    }

    /**
     * A description of the entire PHP function.
     *
     * @param Builder $builder The query builder instance.
     * @param string $column 
     * @return Builder The query builder instance with the where clause applied.
     */
    public function scopeIsCurrent($builder, $column = null)
    {
        return $builder->where($column ?? $this->getCurrentName(), $this->getCurrentConstant());
    }

    public function getCurrentChecking(): bool
    {
        return $this->current_checking;
    }

    /**
     * Check if the current value is set for the given query.
     *
     * @param mixed $query The query builder or model instance.
     * @return bool True if the current value is set, otherwise false.
     */
    public function isHasCurrent($query)
    {
        return in_array($this->getCurrentName(), $query->getFillable());
    }

    /**
     * Set the current value of the object based on the given arguments.
     *
     * @param mixed $query The query builder or model instance.
     * @return void
     */
    public function currentChecking(&$query)
    {
        if ($this->isHasCurrent($query)) {
            if (!isset($query->{$this->getCurrentName()}))
                $query->{$this->getCurrentName()} = $this->getCurrentConstant();
        }
    }

    /**
     * Get the name of the current value.
     *
     * @return string The name of the current value.
     */
    public function getCurrentName(): string
    {
        return $this->__current_name;
    }

    /**
     * Get the value of the current value.
     *
     * @return string The value of the current value.
     */
    public function getCurrentConstant(): string
    {
        return $this->__is_current;
    }

    /**
     * Get the value of the not current value.
     *
     * @return string The value of the not current value.
     */
    public function getNotCurrentConstant(): string
    {
        return $this->__is_not_current;
    }

    /**
     * Set the current value of the object based on the given arguments.
     *
     * @param mixed $args The arguments to determine the current value.
     * @throws \Some_Exception_Class Description of the exception that may be thrown.
     * @return void
     */
    public function setCurrent($query, $args = null)
    {
        $where = [[$query->getKeyName(), "<>", $query->getKey()]];

        $args ??= ($query->getConditions() ?? []);
        $newModel = app($query::class);
        /** IF ARGS IS ARRAY */
        if (is_array($args)) {
            foreach ($args as $key => $arg) {
                if (!isset($query->{$arg})) {
                    $newModel = $newModel->whereNull($arg);
                } else {
                    $where[] = [$arg, $query->{$arg}];
                }
            }
        } else {
            /** ELSE ARGS STAND ALONE VARIABLE */
            $where[] = [$args, $query->{$args}];
        }

        /** SET CURRENT */
        $newModel->where($where)->update([
            "current" => $this->__is_not_current
        ]);
    }

    /**
     * Set the old value of the object based on the given arguments.
     *
     * @param mixed $query The query builder or model instance.
     * @throws \Some_Exception_Class Description of the exception that may be thrown.
     * @return void
     */
    public function setOld($query)
    {
        if ($this->isHasCurrent($query)) {
            if (isset($query->{$this->getCurrentName()})) $this->setCurrent($query);
        }
    }
}
