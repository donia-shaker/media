<?php

namespace DoniaShaker\MediaLibrary;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

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
        ], 'media');
    }

    /**
     * Register the service provider.
     *
     *  @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/media.php', 'media');
    }

}
