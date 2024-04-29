<?php

namespace DoniaShaker\MediaLibrary;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    /**
     * Initializes the application during the booting process.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/media.php' => config_path('media.php')
        ]);
    }

    /**
     * Register the service provider.
     *
     *  @return void
     */
    public function register()
    {
    }
}