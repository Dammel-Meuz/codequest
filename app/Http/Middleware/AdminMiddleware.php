<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est administrateur
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403, 'Accès interdit. Cette page est réservée aux administrateurs.');
        }
        return $next($request);
    }
}
