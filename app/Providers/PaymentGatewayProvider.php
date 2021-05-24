<?php

namespace App\Providers;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Http\Controllers\V1\CmiController;
use App\Http\Controllers\V1\PayLaterController;
use App\Http\Controllers\V1\TinggController;
use App\PaymentGateway\CmiPaymentGateway;
use App\PaymentGateway\PayLaterGateway;
use App\PaymentGateway\TinggPaymentGateway;
use App\Http\Controllers\V1\PaygateController;
use App\Http\Controllers\V1\PayPalController;
use App\PaymentGateway\PaygatePaymentGateway;
use App\Http\Controllers\V1\GlobalirisController;
use App\PaymentGateway\GlobalirisPaymentGateway;
use App\Http\Controllers\V1\FawryController;
use App\PaymentGateway\FawryPaymentGateway;
use App\PaymentGateway\PayPalPaymentGateway;
use Illuminate\Support\ServiceProvider;

class PaymentGatewayProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // example
        /*$this->app->when(ExampleController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(ExamplePaymentGateway::class);
            });*/
        $this->app->when(PayLaterController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(PayLaterGateway::class);
            });
        $this->app->when(GlobalirisController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(GlobalirisPaymentGateway::class);
            });
        $this->app->when(CmiController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(CmiPaymentGateway::class);
            });
        $this->app->when(PaygateController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app){
                return $app->make(PaygatePaymentGateway::class);
            });
        $this->app->when(TinggController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(TinggPaymentGateway::class);
            });
        $this->app->when(FawryController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(FawryPaymentGateway::class);
            });
        $this->app->when(PayPalController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(PayPalPaymentGateway::class);
            });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
