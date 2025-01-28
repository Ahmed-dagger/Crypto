<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The namespace for the controllers in the application.
     *
     * @var string
     */
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * The path to the "web" routes file for the application.
     *
     * @var string
     */
    protected $webRoutesPath = 'routes/web.php';

    /**
     * The path to the "api" routes file for the application.
     *
     * @var string
     */
    protected $apiRoutesPath = 'routes/api.php';

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // You can register any services here if needed
    }

    /**
     * Define the application's route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        // Dynamically load routes for API and Web
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path($this->apiRoutesPath));  // Dynamically call base_path()

        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path($this->webRoutesPath));  // Dynamically call base_path()
    }
}
