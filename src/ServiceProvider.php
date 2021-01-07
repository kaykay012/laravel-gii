<?php

namespace kaykay012\laravelgii;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->commands([
            CurdMakeCommand::class,
            ModelMakeCommand::class,
            ViewVueMakeCommand::class,
            WikiMakeCommand::class,
            ViewBladeMakeCommand::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return [
            CurdMakeCommand::class,
            ModelMakeCommand::class,
            ViewVueMakeCommand::class,
            WikiMakeCommand::class,
            ViewBladeMakeCommand::class,
        ];
    }

}
