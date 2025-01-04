<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\InfoObject;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        Schema::defaultStringLength(191);

        Scramble::extendOpenApi(function (OpenApi $openApi) {
            $openApi->setInfo(new InfoObject("The Guardian", "1.0.0"));
            $openApi->secure(
                SecurityScheme::http('bearer', 'JWT')
                    ->setDescription('JWT Bearer Token. (`Bearer ` is prepended automatically)')
            );
        });

        JsonResource::withoutWrapping();
    }
}
