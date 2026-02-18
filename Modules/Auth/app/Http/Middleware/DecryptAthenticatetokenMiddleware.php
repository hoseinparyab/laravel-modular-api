<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class DecryptAthenticatetokenMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if ($request->headers->has('Authorization')) {
                $token = Crypt::decryptString(str_replace('Bearer ', "", $request->header('Authorization')));
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
            if ($request->cookies->has('x-web-token')) {
                $token = Crypt::decryptString($request->cookies->get('x-web-token'));
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        } catch (\Exception $e) {
            // If decryption fails, we can choose to ignore it and let Sanctum handle the invalid token
        }
        return $next($request);
    }
}
