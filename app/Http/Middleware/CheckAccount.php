<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccount
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
       
        // Verifique se o usuário está autenticado
        if (Auth::check()) {
            $user = Auth::user();
    
            // Lógica para verificar a conta do usuário
            if ($user->check != true) {
                // Redirecionar para uma página de erro ou logout
                return redirect('/verificacao/conta');
            }
        } else {
            // Se não estiver autenticado, redirecionar para a página de login
            return redirect()->route('login');
        }

        return $next($request);
    }
}
