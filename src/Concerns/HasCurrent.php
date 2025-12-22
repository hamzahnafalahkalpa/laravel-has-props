<?php

namespace Hanafalah\LaravelHasProps\Concerns;

trait HasCurrent
{
    public bool $current_checking      = true;

    protected string $__current_name = 'current';

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
    public function scopeIsCurrent($builder, ?array $wheres = null, $column = null)
    {
        if (isset($wheres) && count($wheres) > 0) {
            foreach ($wheres as $where) {
                if (!is_array($where)) throw new \Exception('Filter condition must be an array of arrays');
                $builder = $builder->where($where[0], $where[1]);
            }
        }
        return $builder->limit(1)->orderBy($column ?? $this->getCurrentName(), 'desc');
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
    public function setCurrent(&$query)
    {
        if ($this->isHasCurrent($query) && !isset($query->{$this->getCurrentName()})) {
            $query->{$this->getCurrentName()} = now();
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
}
