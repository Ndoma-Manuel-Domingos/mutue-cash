<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AlterarSenhaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function changePassword(Request $request)
    {
        return Inertia::render('AlterarSenha');
    }

    public function changePasswordPost(Request $request)
    {
        // |confirmed
        $request->validate([
            'new_password' => 'required|min:8',
            'old_password' => 'required|min:8',
        ], 
        [
            'new_password.required' => "Compo Obrigatório",
            'old_password.required' => "Compo Obrigatório",
            'new_password.min' => "O Compo deve ter no minimo 8 caracteres",
            'old_password.min' => "O Compo deve ter no minimo 8 caracteres",
        ]
        );
        
        $user = Auth::user();
        
        if($user->password != md5($request->old_password)){
            return back()->withErrors([
                "old_password" => "Senha antiga não valida!",
            ]);
        }
        
        $user->password = md5($request->new_password);
        $user->last_password_change = Carbon::now();
        $user->save();
        
        return redirect()->route('mc.dashboard');
        
    }

    //
}
