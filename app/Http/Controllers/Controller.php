<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function successResponse($message, $data = [], $code = 200, $cookies = [])
    {
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

    public function errorResponse($message, $code = 400, $data = [])
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
