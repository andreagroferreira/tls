<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Http\Request;
use Src\Shared\Application\ServiceResolver;

class RedirectToController extends Controller
{
    /**
     * @throws EntryNotFoundException
     */
    public function __invoke(Request $request): ?string
    {
        return ServiceResolver::fromString($request->gatewayName)->handle($request);
    }
}
