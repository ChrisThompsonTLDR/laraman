<?php

namespace Christhompsontldr\Laraman;

use Route;
use Illuminate\Routing\Router;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{

    public function boot()
    {
        $this->loadViewsFrom(realpath(dirname(__DIR__) . '/resources/views'), config('laraman.view.hintpath'));
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

        $this->app->register('Collective\Html\HtmlServiceProvider');

        config(['laraman.route.prefixDot' => config('laraman.route.prefix') . ((empty(config('laraman.route.prefix'))) ?: '.')]);
    }

    public static function resource($path, $controller = '', array $options = [])
    {
        //  auto build controller name
        if (empty($controller)) {
            $controller = title_case(str_singular($path)) . 'Controller';
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

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
//    protected $defer = false;

    /**
     * The commands to be registered.
     *
     * @var array
     */
/*    protected $commands = [
        'AddTrait' => 'command.laraboard.add-trait',
        'Migrations' => 'command.laraboard.migrations',
        'Setup' => 'command.laraboard.setup',
    ];*/

/*    public function boot()
    {*/
        // publish configs
/*        $this->publishes([
           realpath(dirname(__DIR__)) . '/config/laraman.php' => config_path('laraman.php'),
        ]);*/

/*        $this->loadViewsFrom(realpath(__DIR__ . '/resources/views'), 'laraboard');

        if (!$this->app->routesAreCached()) {
            $this->setupRoutes($this->app->router);
        }*/
/*    }*/

    /**
     * Get the services provided.
     *
     * @return array
     */
/*    public function provides()
    {
        return array_values($this->commands);
    }*/

    /**
    * Register the providers that are used
    *
    */
/*    public function register()
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();

        $loader->alias('Laratrust', 'Laratrust\LaratrustFacade');
        $loader->alias('Form',      'Collective\Html\FormFacade');
        $loader->alias('Html',      'Collective\Html\HtmlFacade');
        $loader->alias('Markdown',  'BrianFaust\Parsedown\Facades\Parsedown');

        $this->app->register('Baum\Providers\BaumServiceProvider');
        $this->app->register('BrianFaust\Parsedown\ServiceProvider');
        $this->app->register('Collective\Html\HtmlServiceProvider');
        $this->app->register('Laravel\Scout\ScoutServiceProvider');
        $this->app->register('Laratrust\LaratrustServiceProvider');

        $this->app->register('Christhompsontldr\Laraboard\Providers\AuthServiceProvider');
        $this->app->register('Christhompsontldr\Laraboard\Providers\EventServiceProvider');
        $this->app->register('Christhompsontldr\Laraboard\Providers\ViewServiceProvider');

        $this->registerCommands();
    }*/

    /**
     * Register the given commands.
     *
     * @return void
     */
/*    protected function registerCommands()
    {
        foreach (array_keys($this->commands) as $command) {
            $method = "register{$command}Command";
            call_user_func_array([$this, $method], []);
        }
        $this->commands(array_values($this->commands));
    }*/

/*    protected function registerAddTraitCommand()
    {
        $this->app->singleton('command.laraboard.add-trait', function () {
            return new AddTraitCommand();
        });
    }

    protected function registerMigrationsCommand()
    {
        $this->app->singleton('command.laraboard.migrations', function () {
            return new MigrationsCommand();
        });
    }

    protected function registerSetupCommand()
    {
        $this->app->singleton($this->commands['Setup'], function () {
            return new SetupCommand();
        });
    }*/

    /**
     * Define the routes for the package.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
/*    public function setupRoutes(Router $router)
    {
        $router->group(['namespace' => 'Christhompsontldr\Laraboard\Http\Controllers'], function($router)
        {
            require __DIR__.'/Http/routes.php';
        });
    }*/
}