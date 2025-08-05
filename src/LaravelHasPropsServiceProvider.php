<?php

namespace Hanafalah\LaravelHasProps;

use Hanafalah\LaravelHasProps\Events\PropSubjectUpdated;
use Hanafalah\LaravelSupport\Providers\BaseServiceProvider;
use Illuminate\Support\Facades\Event;

class LaravelHasPropsServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return $this
     */
    public function register()
    {
        $this->registerMainClass(LaravelHasProps::class)->registers(['*']);
    }

    public function boot(){
        // Listener
        Event::listen(
            PropSubjectUpdated::class,
            [\Hanafalah\LaravelHasProps\Listeners\DispatchPropJobListener::class, 'handle']
        );
    }

    /**
     * Get the base path of the package.
     *
     * @return string
     */
    protected function dir(): string
    {
        return __DIR__ . '/';
    }

    protected function migrationPath(string $path = ''): string
    {
        return database_path($path);
    }
}
