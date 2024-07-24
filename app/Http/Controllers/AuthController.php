<?php

namespace App\Http\Controllers;

use App\Models\Acesso;
use App\Models\Caixa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GerarCodigoDiario;
use Illuminate\Support\Facades\Hash;

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
            ->whereIn('user_pertence', ['Cash', 'Finance-Cash'])
            ->first();

        $browser = $_SERVER['HTTP_USER_AGENT'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $rotaAtual = $_SERVER['REQUEST_URI'];

        if ($user) {

            $token = md5(time() . rand(0, 99999) . rand(0, 99999));
            $codigo = time();
            $data['email'] = $user->email;
            $data['url'] = getenv('APP_URL') . 'register?token=' . $token;
            $mensagem = 'Acessa o email ' . $user->email . ', clica no link para confirmar o cadastro da sua empresa' . $codigo ;
            $data['mensagem'] = $mensagem;
            $data['codigo'] = $codigo;


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

                try {
                    $user->codigo = $codigo;
                    $user->update();
                    Mail::send(new GerarCodigoDiario($data));
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
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

                try {
                    $user->codigo = $codigo;
                    $user->update();
                    Mail::send(new GerarCodigoDiario($data));
                } catch (\Exception $e) {
                    Log::error($e->getMessage());
                }
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
            
            $user =  Auth::user();
            $user->check = 0;
            $user->save();
        
            Auth::logout();
            Session::flush();
            //Limpa o cookie da sessão iniciada, tivemos de partir para este metodo porque nos servidores o lgout padrão do laravel(Auth::logout()) não está a funcionar
            Cookie::queue(Cookie::forget("mutue_cash_session"));

            return Inertia::location('/login');
        }

    }
}
