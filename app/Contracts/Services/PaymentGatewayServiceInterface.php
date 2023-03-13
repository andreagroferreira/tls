<?php

namespace App\Contracts\Services;

use Illuminate\Http\Request;

interface PaymentGatewayServiceInterface
{
    /**
     * Handles the payment request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function handle(Request $request);
}
