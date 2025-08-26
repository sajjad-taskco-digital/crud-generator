<?php

namespace TaskcoDigital\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use TaskcoDigital\CrudGenerator\Console\Commands\MakeCrudCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCrudCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/stubs' => base_path('stubs/crud-generator'),
        ], 'crud-generator-stubs');
    }

    public function register()
    {
        //
    }
}
