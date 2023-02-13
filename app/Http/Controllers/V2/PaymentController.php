<?php

namespace App\Http\Controllers\V2;

use App\Contracts\Services\PaymentGatewayServiceInterface;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller;

class PaymentController extends Controller
{
    /**
     * Undocumented function.
     *
     * @param Request $request
     *
     * @return array
     */
    public function redirTo(Request $request): array
    {
        return $this->resolveService($request->gatewayName)->handle($request);
    }

    public function notify()
    {
        return 'notify';
    }

    public function return()
    {
        return 'return';
    }

    /**
     * Resolves the service class for the given gateway name.
     *
     * @param string $gatewayName
     *
     * @return PaymentGatewayServiceInterface
     *
     * @throws EntryNotFoundException|\Exception
     */
    public function resolveService(string $gatewayName): PaymentGatewayServiceInterface
    {
        try {
            return app('App\\Services\\PaymentGateways\\'.Str::ucfirst($gatewayName));
        } catch (BindingResolutionException $e) {
            throw new EntryNotFoundException('Payment Controller Error. Service class not found.', 404, $e);
        } catch (\Exception $e) {
            Log::error('General Payment Controller Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
