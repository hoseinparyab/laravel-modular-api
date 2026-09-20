<?php
namespace Modules\Base\Traits;

trait ApiResponse
{
    public function successResponse($message, array $data = [], int $code = 200, array $cookies = []): JsonResponse
    {
        $response = response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
        foreach ($cookies as $value) {
            $response->withCookie($value);
        }
        return $response;
    }
    public function errorResponse($message, array $data = [], array $errors = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }
}
