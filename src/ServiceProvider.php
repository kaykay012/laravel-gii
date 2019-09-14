<?php

namespace kaykay012\laravelgii;

class ServiceProvider extends Illuminate\Support\ServiceProvider
{

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->commands([
            CurdMakeCommand::class,
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
        ];
    }

}
