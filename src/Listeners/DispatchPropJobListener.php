<?php

namespace Hanafalah\LaravelHasProps\Listeners;

use Hanafalah\LaravelHasProps\Events\PropSubjectUpdated;
use Hanafalah\LaravelHasProps\Jobs\HandlePropSubject;

class DispatchPropJobListener
{
    public function handle(PropSubjectUpdated $event)
    {
        HandlePropSubject::dispatch($event->model, $event->tenant)->onQueue('SyncingProps');
    }
}


// <!-- <?php

// namespace Hanafalah\LaravelHasProps\Listeners;

// use Hanafalah\LaravelHasProps\Events\PropSubjectUpdated;
// use Hanafalah\MicroTenant\Facades\MicroTenant;
// use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
// use Illuminate\Database\Eloquent\Relations\Relation;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str;

// class UpdateReferenceFromSubject implements ShouldQueueAfterCommit
// {
//     public function handle(PropSubjectUpdated $event)
//     {
//         Log::info('log nya gak masuk ini');
//         // MicroTenant::tenantImpersonate($event->tenant);
//         // tenancy()->initialize($event->tenant);
//         // $model = $event->model;

//         // $configs = $model->configFromSubject()->get();
//         // foreach ($configs as $config) {
//         //     if (!isset($config->list_config)) continue;

//         //     $lists = $config->list_config;
//         //     Log::info('tes',[$lists]);
//         //     if ($config && $config->reference_type && $config->reference_id) {
//         //         $referenceModel = app(Relation::morphMap()[$config->reference_type] ?? $config->reference_type);
//         //         $referenceModel = $referenceModel::find($config->reference_id);

//         //         if (!$referenceModel) continue;
//         //         list($lists, $new) = $model->addListConfig($config,$model,$lists);
//         //         $referenceModel->setAttribute('prop_' . Str::snake($config->subject_type), $new);
//         //         $referenceModel->saveQuietly();
//         //     }
//         // }
//     }
// } -->