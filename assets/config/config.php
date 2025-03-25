<?php

use Hanafalah\LaravelHasProps\Models as LaravelHasPropsModels;

return [
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts'
    ],
    'database' => [
        'models' => [
            'ConfigProp' => LaravelHasPropsModels\ConfigProp::class,
        ]
    ]
];
