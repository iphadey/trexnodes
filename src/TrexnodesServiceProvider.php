<?php

namespace Iphadey\Trexnodes;

use Illuminate\Support\ServiceProvider;

class TrexnodesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register your service bindings here
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/trexnodes.php' => config_path('trexnodes.php'),
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'trexnodes');
    }
}
