<?php

namespace Hanafalah\LaravelHasProps\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait HasConfigProps
{
    public static function bootHasConfigProps()
    {
        static::updating(function ($model) {       
            $skip_list_config = (
                method_exists($model, 'getConnectionName') && 
                $model->getConnectionName() !== app(config('database.models.ConfigProp'))->getConnectionName()
            );
            if (!$skip_list_config) {
                $tenant = null;
                if (config('micro-tenant') !== null && config('micro-tenant.enabled')) {
                    $tenant = tenancy()->tenant;
                }
                // event(new \Hanafalah\LaravelHasProps\Events\PropSubjectUpdated($model,$tenant));
                $configs = $model->configFromSubject()->get();            
                foreach ($configs as $config) {
                    if (!isset($config->list_config)) continue;
                    $lists = $config->list_config;
                    if ($config && $config->reference_type && $config->reference_id) {
                        $referenceModel = app(Relation::morphMap()[$config->reference_type] ?? $config->reference_type);
                        $referenceModel = $referenceModel::find($config->reference_id);
                        list($lists, $new) = $model->addListConfig($config,$model,$lists);
                        $referenceModel->setAttribute('prop_' . Str::snake($config->subject_type), $new);
                        $referenceModel->saveQuietly();
                    }
                }
            }
        });
    }

    public function getPropAttributes(): array{
        return $this->prop_attributes ?? [];
    }

    public function propResource(object|string $model, mixed $resource = null, array $excepts = []){
        $raw_morph = (is_object($model) ? $model->getMorphClass() : $model);
        $morph = $this->{"prop_" . \strtolower($raw_morph)};
        if (isset($morph)) {
            $request    = new Request($morph);
            $resource ??= $this->getPropAttributes()[$raw_morph] ?? null;
            if (!isset($resource)) throw new \Exception('Prop resource not found', 422);

            if (is_array($resource)) {
                $result = $morph;
            } else {
                $result = new $resource($model);
                $result = $result->toArray($request);
            }
            foreach ($excepts as $except) {
                unset($result[$except]);
            }
            return $result;
        }
        return null;
    }

    public function configFromReference(){return $this->morphOneModel('ConfigProp', 'reference');}
    public function configFromSubject(){return $this->morphOneModel('ConfigProp', 'subject');}

    private function runResource($resource, $data): array{
        return (new $resource($data))->resolve();
    }

    public function listenProp(Model $model, mixed $attr = null){
        $config = $this->configFromReference()->firstOrCreate([
            'reference_type' => $this->getMorphClass(),
            'reference_id'   => (string) $this->getKey(),
            'subject_type'   => $model->getMorphClass(),
            'subject_id'     => (string) $model->getKey()
        ]);
        list($new, $config) = $this->addListConfig($config, $model, $attr);
    }

    public function sync($model, mixed $attr = null){
        if (isset($attr)) {
            if (is_string($attr) && class_exists($attr)) {
                $attr = (new $attr($model))->toArray();
            }
        } else {
            $model_prop = $this->getPropAttributes()[$model->getMorphClass()];
            if (isset($model_prop)) {
                if (is_string($model_prop) && class_exists($model_prop)) {
                    $attr = $this->runResource($model_prop, $model);
                } elseif (is_callable($attr)) {
                    $attr = $attr($model);
                } else {
                    $attr = $model_prop;
                }
            }
        }

        if (!isset($attr)) return $this;
        $config = $this->configFromReference()->firstOrCreate([
            'reference_type' => $this->getMorphClass(),
            'reference_id'   => (string) $this->getKey(),
            'subject_type'   => $model->getMorphClass(),
            'subject_id'     => (string) $model->getKey()
        ]);

        list($new, $config) = $this->addListConfig($config, $model, $attr);
        $this->setAttribute('prop_' . strtolower($model->getMorphClass()), $new);
        $this->save();
        return $this;
    }

    private function addListConfig($config,$model,$attr): array{
        list($lists, $new) = $this->propGenerate($attr, $model);
        $config->setAttribute('list_config', $lists);
        $config->setAttribute('current_data', $new);
        $config->save();
        return [$new,$config];
    }

    private function propGenerate(array $attr, ?Model $model = null){
        $new = [];
        $lists = [];
        $is_assoc = array_is_list($attr);
        if (isset($model)){
            if (!$is_assoc) {
                foreach ($attr as $key => $attribute) {
                    $new[$key] = $attribute;
                    $lists[] = $key;
                }
            } else {
                foreach ($attr as $key) {
                    if (isset($model->{$key})) {
                        $new[$key] = $model->{$key};
                        $lists[]   = $key;
                    }
                }
            }
        }else{
            foreach ($attr as $key) {
                $lists[] = $key;
            }
        }
        return [$lists, $new];
    }

    public function scopeSubject($builder, object $model)
    {
        return $this->morphRef($builder, $model, 'subject');
    }

    public function scopeReference($builder, object $model)
    {
        return $this->morphRef($builder, $model, 'reference');
    }

    private function morphRef($builder, object $model, string $morph)
    {
        return $builder->where(function ($builder) use ($model, $morph) {
            $builder->where($morph . '_type', $model->getMorphClass());
            if ($model->getKey() !== null) {
                $builder->where($morph . '_id', $model->getKey());
            }
        });
    }
}
