<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    //
    use TraitPerfil;

    public function login()
    {
        return Inertia::render('Login');
    }

    public function autenticacao(Request $request)
    {

        $request->validate([
            "email" => ["required"],
            "password" => ["required"],
        ], [
            "email.required" => "Campo Obrigatório",
            "password.required" => "Campo Obrigatório"
        ]);

        $user = User::where('userName', $request->get('email'))
        ->where('password', md5($request->password))
        ->first();
        
        if($user){
            
            if(!$this->user_validado($user)){
                return back()->withErrors([
                    "acesso" => "Acesso registro",
                ]);
            }else{
                Auth::login($user);
                // LoginAcesso::create([ 'ip' => $request->ip(), 'maquina' => "", 'browser' => $request->userAgent(), 'user_name' => $request->user()->nome, 'outra_informacao' => $request->path(), 'user_id' => $request->user()->pk_utilizador]);
                return  redirect()->route('mc.dashboard');
            }
        }

        return back()->withErrors([
            "email" => "Dados Invalidos",
            "password" => "Dados Invalidos",
        ]);
    }

    public function logout()
    {
        Auth::logout();

        return Inertia::location('/login');
    }
}
