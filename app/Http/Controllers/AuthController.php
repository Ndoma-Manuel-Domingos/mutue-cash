<?php

namespace App\Http\Controllers;

use App\Models\Caixa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    //
    use TraitPerfil;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

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

        if ($user) {

            if (!$this->user_validado($user)) {
                return back()->withErrors([
                    "acesso" => "Acesso registro",
                ]);
            } else {
                if ($user->codigo_importado == null) {
                    $user->update(['codigo_importado' => $user->pk_utilizador]);
                }
                Auth::login($user);
                // LoginAcesso::create([ 'ip' => $request->ip(), 'maquina' => "", 'browser' => $request->userAgent(), 'user_name' => $request->user()->nome, 'outra_informacao' => $request->path(), 'user_id' => $request->user()->pk_utilizador]);
                return redirect()->route('mc.dashboard');
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
        
        // dd();
    
        // $verificar_caixa_aberto = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        // $message = "Por favor! antes de sair do sistema pedimos que faça o fecho do caixa que abriu.";
        // $messag2 = "Gostariamos de lembrar ao caro utilizador que não fez o fecho do caixa que abriu.";

        // if ($verificar_caixa_aberto) {
        //     return response()->json(['message' => $message, 'status' => 201]);
        // }else{
            
        // }
    }
}
