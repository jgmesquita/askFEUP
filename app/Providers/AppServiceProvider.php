<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Blade;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if(env('FORCE_HTTPS',false)) {
            error_log('configuring https');

            $app_url = config("app.url");
            URL::forceRootUrl($app_url);
            $schema = explode(':', $app_url)[0];
            URL::forceScheme($schema);
        }

        Blade::directive('csrf', function () {
            return '<?php echo \'<input type="hidden" name="_token" value="\' . csrf_token() . \'">\'; ?>';
        });
    }
}
