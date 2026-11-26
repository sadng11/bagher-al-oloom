<?php

namespace App\Providers;

use App\Models\Photo;
use App\Models\PhotoAlbum;
use App\Observers\PhotoAlbumObserver;
use App\Observers\PhotoObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // if ($this->app->environment('local')) {
        //     $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        //     $this->app->register(TelescopeServiceProvider::class);
        // }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        error_reporting(E_ERROR);
        Photo::observe(PhotoObserver::class);
        PhotoAlbum::observe(PhotoAlbumObserver::class);
        Queue::failing(function (JobFailed $event) {
            Log::info($event->connectionName);
            // $event->connectionName
            // $event->job
            // $event->exception
        });
    }
}
