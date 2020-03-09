<?php

namespace ArtemSchander\L5Modular;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Factory;

class ModuleServiceProvider extends ServiceProvider
{
    protected $files;

    /**
     * Bootstrap the application services.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    public function boot(Filesystem $files)
    {
        $this->files = $files;
        if (is_dir(app_path('Modules'))) {

            $modules = array_map('class_basename', $this->files->directories(app_path('Modules')));
            foreach ($modules as $module) {
                // Allow routes to be cached
                $this->registerModule($module);
            }
        }
    }

    /**
     * Register a module by its name
     *
     * @param  string $name
     *
     * @return void
     */
    protected function registerModule(string $name)
    {
        $enabled = config("modules.specific.{$name}.enabled", true);
        if ($enabled) {
            $this->registerRoutes($name);
            $this->registerHelpers($name);
            $this->registerViews($name);
            $this->registerTranslations($name);
            $this->registerMigrations($name);
            $this->registerFactories($name);
        }
    }

    /**
     * Register the routes for a module by its name
     *
     * @param  string $module
     *
     * @return void
     */
    protected function registerRoutes(string $module)
    {
        if (! $this->app->routesAreCached()) {
            extract($this->getRoutingConfig($module));

            foreach ($types as $type) {
                $this->registerRoute($module, $path, $namespace, $type, $file);
            }
        }
    }

    /**
     * Registeres a simgle route
     *
     * @param  string $module
     * @param  string $path
     * @param  string $namespace
     * @param  string $type
     *
     * @return void
     */
    protected function registerRoute(string $module, string $path, string $namespace, string $type)
    {
        if ($type === 'simple') $file = 'routes.php';
        else $file = "{$type}.php";

        $file = str_replace('//', '/', app_path("Modules/{$module}/{$path}/{$file}.php"));

        $allowed = [ 'web', 'api', 'simple' ];
        if (in_array($type, $allowed) && $this->files->exists($file)) {
            if ($type === 'simple') {
                Route::namespace($namespace)->group($file);
            } else {
                Route::middleware($type)->namespace($namespace)->group($file);
            }
        }
    }

    /**
     * Collect the needed data to register the routes
     *
     * @param  string $module
     *
     * @return array
     */
    protected function getRoutingConfig(string $module)
    {
        $types = config("modules.specific.{$module}.routing", config('modules.default.routing'));
        $path = config("modules.specific.{$module}.structure.routes", config('modules.default.structure.routes'));

        $cp = config("modules.specific.{$module}.structure.controllers", config('modules.default.structure.controllers'));
        $namespace = trim("App\\Modules\\{$module}\\" . implode('\\', explode('/', $cp)), '\\');

        return compact('types', 'path', 'namespace');
    }

    /**
     * Register the helpers file for a module by its name
     *
     * @param  string $module
     *
     * @return void
     */
    protected function registerHelpers(string $module)
    {
        if ($file = $this->prepareComponent($module, 'helpers', 'helpers.php')) {
            include_once $file;
        }
    }

    /**
     * Register the views for a module by its name
     *
     * @param  string $module
     *
     * @return void
     */
    protected function registerViews(string $module)
    {
        if ($views = $this->prepareComponent($module, 'views')) {
            $this->loadViewsFrom($views, $module);
        }
    }

    /**
     * Register the translations for a module by its name
     *
     * @param  string $module
     *
     * @return void
     */
    protected function registerTranslations(string $module)
    {
        if ($translations = $this->prepareComponent($module, 'translations')) {
            $this->loadTranslationsFrom($translations, $module);
        }
    }

    /**
     * Register the migrations for a module by its name
     *
     * @param  string $module
     *
     * @return void
     */
    protected function registerMigrations(string $module)
    {
        if ($migrations = $this->prepareComponent($module, 'migrations')) {
            $this->loadMigrationsFrom($migrations);
        }
    }

    /**
     * Register the factories for a module by its name
     *
     * @param  string $module
     *
     * @return void
     */
    protected function registerFactories(string $module)
    {
        if ($factories = $this->prepareComponent($module, 'factories')) {
            $this->app->make(Factory::class)->load($factories);
        }
    }

    /**
     * Prepare component registration
     *
     * @param  string $module
     * @param  string $component
     * @param  string $file
     *
     * @return string
     */
    protected function prepareComponent(string $module, string $component, string $file = '')
    {
        $path = config("modules.specific.{$module}.structure.{$component}", config("modules.default.structure.{$component}"));
        $resource = rtrim(str_replace('//', '/', app_path("Modules/{$module}/{$path}/{$file}")), '/');

        if (! ($file && $this->files->exists($resource)) && ! (!$file && $this->files->isDirectory($resource))) {
            $resource = false;
        }
        return $resource;
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMakeCommand();
        $this->registerPublishConfig();
    }

    /**
     * undocumented function
     *
     * @return void
     */
    protected function registerPublishConfig()
    {
        $configPath = __DIR__ . '/config/modules.php';
        $publishPath = $this->app->configPath('modules.php');

        $this->publishes([
            $configPath => $publishPath,
        ]);
        $this->mergeConfigFrom($configPath, 'modules');
    }

    /**
     * Register the "make:module" console command.
     *
     * @return Console\ModuleMakeCommand
     */
    protected function registerMakeCommand()
    {
        $this->commands('modules.make');

        $bind_method = method_exists($this->app, 'bindShared') ? 'bindShared' : 'singleton';

        $this->app->{$bind_method}('modules.make', function ($app) {
            return new Console\ModuleMakeCommand($this->files);
        });
    }
}
