<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->route('locale');
        abort_unless(in_array($locale, ['lt','en','ru']), 404);
        app()->setLocale($locale);
        \Illuminate\Support\Facades\View::share('locale', $locale);
        return $next($request);
    }
}
