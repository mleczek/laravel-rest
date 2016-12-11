<?php


namespace Mleczek\Rest;


use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Mleczek\Rest\ResponseFactoryMacros;

class RestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/rest.php' => config_path('rest.php'),
            __DIR__.'/Providers/ContextServiceProvider.php' => app_path('Providers/ContextServiceProvider.php'),
        ]);

        // Register response macros, usage:
        // response()->noContent(...);
        $macros = $this->app->make(ResponseFactoryMacros::class);
        Response::macro('item', [$macros, 'item']);
        Response::macro('collection', [$macros, 'collection']);
        Response::macro('accepted', [$macros, 'accepted']);
        Response::macro('noContent', [$macros, 'noContent']);
        Response::macro('created', [$macros, 'created']);
        Response::macro('updated', [$macros, 'updated']);
        Response::macro('patched', [$macros, 'patched']);
        Response::macro('deleted', [$macros, 'deleted']);
    }

    /**
     * Register bindings in the container.
     */
    public function register()
    {
        // Default package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/rest.php', 'rest');

        $this->app->singleton(RequestParser::class);
        $this->app->singleton(ContextRepository::class);

        // TODO: Aliases (facades)...
    }
}