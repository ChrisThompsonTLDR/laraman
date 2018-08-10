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
    }

    public static function resource($path, $controller = '', array $options = [])
    {
        //  auto build controller name
        if (empty($controller)) {
            $controller = str_replace(' ', '', title_case(str_replace('-', ' ', str_singular($path)))) . 'Controller';
        }

        Route::post($path . '/filter', ['as' => $path . '.filter', 'uses' => $controller . '@filter']);
        Route::post($path . '/search', ['as' => $path . '.search', 'uses' => $controller . '@search']);

        $options = array_merge([
            'names' => [
                'index'     => $path . '.index',
                'create'    => $path . '.create',
                'store'     => $path . '.store',
                'edit'      => $path . '.edit',
                'update'    => $path . '.update',
                'show'      => $path . '.show',
                'destroy'   => $path . '.destroy',
            ],
            ], $options);

        Route::resource($path, $controller, $options);
    }
}