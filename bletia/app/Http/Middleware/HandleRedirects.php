<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;

class HandleRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $path = '/' . ltrim($request->getPathInfo(), '/');
        $redirect = Redirect::where('from', $path)->first();
        if ($redirect) {
            return redirect($redirect->to, $redirect->status);
        }
        return $next($request);
    }
}
