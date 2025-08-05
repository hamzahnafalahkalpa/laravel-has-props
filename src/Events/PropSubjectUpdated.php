<?php

namespace Hanafalah\LaravelHasProps\Events;

use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use ReflectionClass;

class PropSubjectUpdated
{
    use SerializesModels;

    public function __construct(
        public Model $model,
        public ?Model $tenant = null
    ) {}

    public function __unserialize(array $values): void
    {
        dd($this->tenant);
        if ($this->tenant) {
            MicroTenant::tenantImpersonate($this->tenant);
            tenancy()->initialize($this->tenant);
        }

        $properties = (new ReflectionClass($this))->getProperties();

        $class = get_class($this);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            if (! array_key_exists($name, $values)) {
                continue;
            }

            $property->setValue(
                $this, $this->getRestoredPropertyValue($values[$name])
            );
        }
    }
}
