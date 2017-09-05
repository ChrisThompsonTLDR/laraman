<?php

namespace Christhompsontldr\Laraman;

use Route;
use Illuminate\Routing\Router;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    public function boot()
    {
        $this->loadViewsFrom(realpath(dirname(__DIR__) . '/resources/views'), config('laraman.view.hintpath'));

        $this->publishes([
            realpath(dirname(__DIR__)) . '/config/laraman.php' => config_path('laraman.php'),
        ], 'config');
    }

    public function register()
    {
        $this->app->bind('Laraman', function ($app) {
            return new Laraman($app);
        });

        $loader = \Illuminate\Foundation\AliasLoader::getInstance();

        $loader->alias('Laraman', \Christhompsontldr\Laraman\ServiceProvider::class);
        $loader->alias('Form',    \Collective\Html\FormFacade::class);
        $loader->alias('Html',    \Collective\Html\HtmlFacade::class);

        $this->app->register(\Collective\Html\HtmlServiceProvider::class);

        //  make the config available even if not published
        $this->mergeConfigFrom(
            realpath(dirname(__DIR__)) . '/config/laraman.php', 'laraman'
        );

        config(['laraman.route.prefixDot' => config('laraman.route.prefix') . ((empty(config('laraman.route.prefix'))) ?: '.')]);
    }

    public static function resource($path, $controller = '', array $options = [])
    {
        //  auto build controller name
        if (empty($controller)) {
            $controller = str_replace(' ', '', title_case(str_replace('-', ' ', str_singular($path)))) . 'Controller';
        }

        Route::post($path . '/filter', ['as' => config('laraman.route.prefixDot') . $path . '.filter', 'uses' => $controller . '@filter']);
        Route::post($path . '/search', ['as' => config('laraman.route.prefixDot') . $path . '.search', 'uses' => $controller . '@search']);

        $options = array_merge([
            'names' => [
                'index'     => config('laraman.route.prefixDot') . $path . '.index',
                'create'    => config('laraman.route.prefixDot') . $path . '.create',
                'store'     => config('laraman.route.prefixDot') . $path . '.store',
                'edit'      => config('laraman.route.prefixDot') . $path . '.edit',
                'update'    => config('laraman.route.prefixDot') . $path . '.update',
                'show'      => config('laraman.route.prefixDot') . $path . '.show',
                'destroy'   => config('laraman.route.prefixDot') . $path . '.destroy',
            ],
            ], $options);

        Route::resource($path, $controller, $options);
    }
}