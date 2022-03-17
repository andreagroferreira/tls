<?php

namespace App\Providers;

use App\Contracts\PaymentGateway\PaymentGatewayInterface;
use App\Http\Controllers\V1\ClictopayController;
use App\Http\Controllers\V1\CmiController;
use App\Http\Controllers\V1\KBankController;
use App\Http\Controllers\V1\PayBankController;
use App\Http\Controllers\V1\PayLaterController;
use App\Http\Controllers\V1\PaysoftController;
use App\Http\Controllers\V1\PayuController;
use App\Http\Controllers\V1\TinggController;
use App\Http\Controllers\V1\AlipayController;
use App\PaymentGateway\AlipayPaymentGateway;
use App\PaymentGateway\ClictopayPaymentGateway;
use App\PaymentGateway\CmiPaymentGateway;
use App\PaymentGateway\KBankPaymentGateway;
use App\PaymentGateway\PayBankGateway;
use App\PaymentGateway\PayLaterGateway;
use App\PaymentGateway\PaysoftPaymentGateway;
use App\PaymentGateway\PayuPaymentGateway;
use App\PaymentGateway\TinggPaymentGateway;
use App\Http\Controllers\V1\PaygateController;
use App\Http\Controllers\V1\PayPalController;
use App\PaymentGateway\PaygatePaymentGateway;
use App\Http\Controllers\V1\GlobalirisController;
use App\PaymentGateway\GlobalirisPaymentGateway;
use App\Http\Controllers\V1\FawryController;
use App\PaymentGateway\FawryPaymentGateway;
use App\PaymentGateway\PayPalPaymentGateway;
use App\Http\Controllers\V1\PayfortController;
use App\PaymentGateway\PayfortPaymentGateway;
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
        $this->app->when(PayuController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(PayuPaymentGateway::class);
            });
        $this->app->when(PayBankController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(PayBankGateway::class);
            });
        $this->app->when(KBankController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(KBankPaymentGateway::class);
            });
        $this->app->when(PayfortController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(PayfortPaymentGateway::class);
            });
        $this->app->when(ClictopayController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(ClictopayPaymentGateway::class);
            });
        $this->app->when(PaysoftController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(PaysoftPaymentGateway::class);
            });
        //支付宝支付
        $this->app->when(AlipayController::class)
            ->needs(PaymentGatewayInterface::class)
            ->give(function ($app) {
                return $app->make(AlipayPaymentGateway::class);
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
