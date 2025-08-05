<?php

namespace Hanafalah\LaravelHasProps\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\MicroTenant\Facades\MicroTenant;

class HandlePropSubject implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Model $model,
        public ?Model $tenant = null
    ) {}

    public function handle()
    {
        // ⏱ DI SINI baru setup tenant
        MicroTenant::tenantImpersonate($this->tenant);
        tenancy()->initialize($this->tenant);

        // 🔧 Proses logic kamu di sini, misalnya:
        // $this->model->something();
    }
}
