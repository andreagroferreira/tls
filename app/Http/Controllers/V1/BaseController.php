<?php


namespace App\Http\Controllers\V1;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Controller as Controller;

/**
 * @OA\Info(
 *     description="API for Payment service project",
 *     version="1.0.0",
 *     title="Payment Service API"
 * )
 */
class BaseController extends Controller
{
    protected function sendResponse($result, $code = 200)
    {
        return response()->json($result, $code);
    }

    protected function sendEmptyResponse($code = 200)
    {
        return response(null, $code);
    }

    protected function sendError($error, $message, $code = 400)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }
        Log::info('status=' . $code . ', error=' . $error . ', message=' . $message);
        return response()->json([
            'status' => 'fail',
            'error' => $error,
            'message' => $message
        ], $code);
    }

    protected function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment')
    {
        $response = response()->stream($callback, 200, $headers);

        if (filled($name)) {
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
                $disposition,
                $name,
                str_replace('%', '', Str::ascii($name))
            ));
        }

        return $response;
    }
}
