<?php

namespace AstraTech\DataForge;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\File;

use AstraTech\DataForge\Handlers\ErrorHandler;
use AstraTech\DataForge\Base\DataForge;
use AstraTech\DataForge\Base\Sql;
use AstraTech\DataForge\Base\Entity;
use AstraTech\DataForge\Base\Task;

class DataForgeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * This method is used to load any resources, such as routes,
     * configuration, views, migrations, etc., that your package
     * needs to register.
     *
     * @return void
     */
    public function boot()
    {
        // Load the package routes from the 'routes/api.php' file
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
        
        $this->publishes([
            __DIR__ . '/../routes/' => base_path('routes/dataforge'),
        ], 'routes');

        // Alias the DataForge class
        if (!class_exists('DataForge'))
            class_alias(DataForge::class, 'DataForge');

        // Alias the Sql class
        if (!class_exists('DataForge\Sql'))
        class_alias(Sql::class, 'DataForge\Sql');

        // Alias the Sql class
        if (!class_exists('DataForge\Entity'))
            class_alias(Entity::class, 'DataForge\Entity');

        // Alias the Sql class
        if (!class_exists('DataForge\Task'))
            class_alias(Task::class, 'DataForge\Task');
        
        // Example: You can add publishing of config files if necessary
        // $this->publishes([
        //     __DIR__ . '/../config/dataforge.php' => config_path('dataforge.php'),
        // ], 'config');

        // Replace Laravel's default exception handler with the custom handler
        $this->app->singleton(ExceptionHandler::class, ErrorHandler::class);
        
        // Ensure the app/DataForge directory exists
        $this->createDataForgeDirectory();
    }

    /**
     * Register any application services.
     *
     * This method is used to register bindings in the service container.
     * If you have classes or services that you want to bind to the Laravel container,
     * you can do that here.
     *
     * @return void
     */
    public function register()
    {
        // Example: Register a custom query handler if needed
        // $this->app->bind('CustomQueryHandler', function ($app) {
        //     return new CustomQueryHandler();
        // });
        
        // Bind the DataForge class to the container
        App::getInstance()->alias('DataForge', \AstraTech\DataForge\Base\DataForge::class);
    }

    /**
     * Create the DataForge directory structure.
     */
    protected function createDataForgeDirectory()
    {
        $baseDirectory = app_path('DataForge');

        // Check if the directory already exists
        if (!File::exists($baseDirectory)) {
            // Create the directory structure
            File::makeDirectory($baseDirectory, 0755, true);
            
            // Optionally create subdirectories
            File::makeDirectory($baseDirectory . '/Sql', 0755, true);
            File::makeDirectory($baseDirectory . '/Entity', 0755, true);
            File::makeDirectory($baseDirectory . '/Task', 0755, true);
        }
    }
}
