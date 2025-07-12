<?php

namespace Hanafalah\LaravelHasProps\Models;

use Hanafalah\LaravelHasProps\Concerns\HasProps;
use Hanafalah\LaravelSupport\Models\BaseModel;

class ConfigProp extends BaseModel{
    use HasProps;

    public $incrementing      = false;
    protected $primaryKey     = "id";      
    protected $keyType        = "string";
    protected $fillable       = [
        'id',
        'reference_type',
        'reference_id',
        'subject_type',
        'subject_id',
        'props',
    ];

    public function reference(){return $this->morphTo();}
    public function subject(){return $this->morphTo();}
}
