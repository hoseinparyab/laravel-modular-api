<?php

namespace Modules\Base\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    public function successResponse(
        string $message,
        mixed $data = [],
        int $code = 200,
        array $cookies = []
    ): JsonResponse {
        $response = response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);

        foreach ($cookies as $cookie) {
            $response->withCookie($cookie);
        }

        return $response;
    }

    public function errorResponse(
        string $message,
        int $code = 400,
        mixed $data = []
    ): JsonResponse {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
