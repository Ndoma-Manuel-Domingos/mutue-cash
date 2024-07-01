<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CheckPasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
    
        $user = Auth::user();
        if ($user && $user->last_password_change) {
            $passwordAge = Carbon::parse($user->last_password_change);    
            if ($passwordAge->diffInDays(Carbon::now()) >= 4) {
                return redirect('/password/change');
            }
        }
        
        return $next($request);
    }
}
