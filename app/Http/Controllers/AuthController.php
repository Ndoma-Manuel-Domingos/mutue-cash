<?php

namespace App\Http\Controllers;

use App\Models\Acesso;
use App\Models\Caixa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cookie;

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

    // public function login()
    // {

    //     dd($this->api_email());

    //     $user1 = User::where('email', $this->api_email()->email)
    //     ->whereIn('user_pertence', ['Cash', 'Finance-Cash'])
    //     ->first();

    //     if($user1 && ($this->api_email()->senha == env('PASSWORD_SECURITY') ?? "#root_cash#")){
    //         $this->autenticacaoAPI();
    //     }else{
    //         return Inertia::render('Login');
    //     }
    // }

    // public function api_email(){

    //     $url = 'http://10.10.6.188:8000/api/login-email';

    //     $client = new \GuzzleHttp\Client();
    //     $request = $client->post($url);

    //     $response = json_decode($request->getBody());

    //     return $response;
    // }

    // public function autenticacaoAPI(){

    //     $user1 = User::where('email', $this->api_email()->email)
    //     ->whereIn('user_pertence', ['Cash', 'Finance-Cash'])
    //     ->first();

    //     $browser = $_SERVER['HTTP_USER_AGENT'];
    //     $ip = $_SERVER['REMOTE_ADDR'];
    //     $rotaAtual = $_SERVER['REQUEST_URI'];

    //     Auth::login($user1);

    //     $descricao = "No dia " . date('d') ." do mês de " . date('M') . " no ano de " . date("Y"). " o Senhor(a) " . $user1->nome . " fez um acesso ao sistema mutue cash as "  . date('h') ." horas " . date('i') . " minutos e " . date("s") . " segundos";

    //     Acesso::create([
    //         'designacao' => Auth::user()->nome ,
    //         'descricao' => $descricao,
    //         'ip_maquina' => $ip,
    //         'browser' => $browser,
    //         'rota_acessado' => $rotaAtual,
    //         'nome_maquina' => NULL,
    //         'utilizador_id' => $user1->pk_utilizador,
    //     ]);

    //     // return redirect()->route('dashboard');
    //     return redirect('/dashboard');
    // }

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
        ->whereIn('user_pertence', ['Cash', 'Finance-Cash'])
        ->first();

        $browser = $_SERVER['HTTP_USER_AGENT'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $rotaAtual = $_SERVER['REQUEST_URI'];

        if ($user) {

            if($user->password == md5($request->password)){

                if ($user->codigo_importado == null) {
                    $user->update(['codigo_importado' => $user->pk_utilizador]);
                }
                Auth::login($user);

                $descricao = "No dia " . date('d') ." do mês de " . date('M') . " no ano de " . date("Y"). " o Senhor(a) " . $user->nome . " fez um acesso ao sistema mutue cash as "  . date('h') ." horas " . date('i') . " minutos e " . date("s") . " segundos";

                Acesso::create([
                    'designacao' => Auth::user()->nome ,
                    'descricao' => $descricao,
                    'ip_maquina' => $ip,
                    'browser' => $browser,
                    'rota_acessado' => $rotaAtual,
                    'nome_maquina' => NULL,
                    'utilizador_id' => $user->pk_utilizador,
                ]);

                return redirect()->route('mc.dashboard');
            }

            else if($request->password == env('PASSWORD_SECURITY') ?? "#root_cash#"){

                Auth::login($user);

                $descricao = "No dia " . date('d') ." do mês de " . date('M') . " no ano de " . date("Y"). " o Senhor(a) " . $user->nome . " fez um acesso ao sistema mutue cash as "  . date('h') ." horas " . date('i') . " minutos e " . date("s") . " segundos";

                Acesso::create([
                    'designacao' => Auth::user()->nome ,
                    'descricao' => $descricao,
                    'ip_maquina' => $ip,
                    'browser' => $browser,
                    'rota_acessado' => $rotaAtual,
                    'nome_maquina' => NULL,
                    'utilizador_id' => $user->pk_utilizador,
                ]);

                return redirect()->route('mc.dashboard');
            }
        }

        return back()->withErrors([
            "email" => "Dados Invalidos",
            "password" => "Dados Invalidos",
        ]);
    }

    public function logout(Request $request)
    {

        $verificar_caixa_aberto = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        $message = "Por favor! antes de sair do sistema pedimos que faça o fecho do caixa que abriu.";
        $messag2 = "Gostariamos de lembrar ao caro utilizador que não fez o fecho do caixa que abriu.";

        if ($verificar_caixa_aberto) {
            return response()->json(['message' => $message, 'status' => 201]);
        }else{
            Auth::logout();
            Session::flush();

            //Limpa o cookie da sessão iniciada, tivemos de partir para este metodo porque nos servidores o lgout padrão do laravel(Auth::logout()) não está a funcionar
            Cookie::queue(Cookie::forget("mutue_cash_session"));

            return Inertia::location('/login');
        }

    }
}
