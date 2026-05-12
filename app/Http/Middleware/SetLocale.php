<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
    $locale = config('app.locale'); // اللغة الافتراضية

    if ($request->is('api/*')) {
        // إذا كان الطلب API، نقرأ اللغة من الـ Header
        //Header: Accept-Language: ar
        $locale = $request->header('Accept-Language', config('app.locale'));
    } else {
        // إذا كان طلب Web، نقرأ من الـ Session
        $locale = session()->get('locale', config('app.locale'));
    }

    // التأكد أن اللغة مدعومة في نظامك
    if (in_array($locale, ['ar', 'en'])) {
        App::setLocale($locale);
    }



    
        return $next($request);
    }
}
