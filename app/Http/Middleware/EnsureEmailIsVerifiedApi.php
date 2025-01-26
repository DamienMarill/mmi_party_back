<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() &&
            $request->user() instanceof MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail()) {

            return response()->json([
                'message' => 'Email non vérifié.',
                'verification_needed' => true
            ], 409);
        }

        return $next($request);
    }
}
