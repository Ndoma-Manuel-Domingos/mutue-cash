<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaixaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositoController;
use App\Http\Controllers\MovimentoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PagamentosController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearhController;
use App\Models\Caixa;
use App\Models\Deposito;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/renomear-arquivos', function () {
    
//     $depositos= Deposito::whereNotNull('codigo_matricula_id')->pluck('codigo_matricula_id')->toArray();

//     $matriculados = DB::table('tb_matriculas')
//     ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
//     ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
//     ->select('tb_preinscricao.Codigo as candidato_id', 'tb_matriculas.Codigo as matricula_id')
//     ->whereIn('tb_matriculas.Codigo', $depositos)->get();
//     foreach ($matriculados as $value) {
//         $deposito = Deposito::where('codigo_matricula_id', $value->matricula_id)->first();
//         if($deposito){
//             $deposito->update(['Codigo_PreInscricao'=>$value->candidato_id]);
//         }
//     }
// });

Route::get('/', [AuthController::class, 'login'])
    ->middleware('guest');

Route::get('/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthController::class, 'autenticacao'])->name('mc.login.post');

Route::group(["middleware" => "auth"], function () {

    // ['prefix' => 'api', 'middleware' => ['auth', 'role_or_permission:Admin|Docano|Reitoria|listar provas']

    Route::post('/logout', [AuthController::class, 'logout'])->name('mc.logout');

    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('mc.dashboard');

    Route::get('/depositos', [DepositoController::class, 'index'])->name('mc.depositos.index');
    Route::post('/depositos/store', [DepositoController::class, 'store'])->name('mc.depositos.store');
    Route::get('/depositos/pdf', [DepositoController::class, 'pdf']);
    Route::get('/depositos/excel', [DepositoController::class, 'excel']);
    Route::get('/depositos/imprimir-comprovativo', [DepositoController::class, 'imprimir']);
    Route::get('/imprimir-comprovativo-ticket', [DepositoController::class, 'ticket']);
    Route::get('/depositos/editar/{id}', [DepositoController::class, 'edit']);
    Route::post('/depositos/update', [DepositoController::class, 'update']);

    Route::get('/movimentos/diarios-operador', [MovimentoController::class, 'diariosOperador']);
    Route::get('/movimentos/caixas-abertos', [MovimentoController::class, 'caixasAbertos']);
    Route::get('/movimentos/registrar-saidas', [MovimentoController::class, 'registrarSaidas']);
    
    Route::get('/movimentos/abertura-caixa', [MovimentoController::class, 'abertura'])->name('mc.movimentos-abertura-caixa');
    Route::post('/movimentos/abertura-caixa', [MovimentoController::class, 'aberturaStore'])->name('mc.movimentos-abertura-caixa-store');
    Route::get('/movimentos/fecho-caixa-por-admin', [MovimentoController::class, 'fechoAdmin']);
    Route::get('/movimentos/fecho-caixa', [MovimentoController::class, 'fecho'])->name('mc.movimentos-fecho-caixa');
    Route::post('/movimentos/fecho-caixa', [MovimentoController::class, 'fechoStore'])->name('mc.movimentos-fecho-caixa-store');
    Route::get('/movimentos/imprimir-comprovativo', [MovimentoController::class, 'imprimir']);
    Route::get('/movimentos/imprimir-pdf', [MovimentoController::class, 'pdf']);
    Route::get('/movimentos/imprimir-excel', [MovimentoController::class, 'excel']);
    Route::get('/movimentos/validar-fecho', [MovimentoController::class, 'validarFechoCaixa'])->name('mc.movimentos-validar-fecho-caixa');

    Route::get('/movimentos/validar-fecho/{id}/validar', [MovimentoController::class, 'validarFechoCaixaAdmin']);
    Route::get('/movimentos/validar-fecho/{id}/{motivo}/cancelar', [MovimentoController::class, 'cancelarFechoCaixaAdmin']);
    Route::get('/movimentos/confirmar-senhar-admin/{id}', [MovimentoController::class, 'confirmarSenhaAdmin']);
    Route::get('/movimentos/bloquear-caixa', [MovimentoController::class, 'bloquearCaixa'])->name('mc.bloquear-caixa');
    Route::post('/movimentos/bloquear-caixa-store', [MovimentoController::class, 'bloquearCaixaStore'])->name('mc.bloquear-caixa-store');
    

    Route::get('/pagamentos', [PagamentosController::class, 'index'])->name('mc.pagamentos.index')->middleware('role_or_permission:Gestor de Caixa|Supervisor|Operador Caixa|criar pagamento|listar pagamento');
    Route::get('/pagamentos/criar', [PagamentosController::class, 'create'])->name('mc.pagamentos.create')->middleware('role_or_permission:Gestor de Caixa|Supervisor|Operador Caixa|criar pagamento|listar pagamento');
    Route::get('/pagamentos/pdf', [PagamentosController::class, 'pdf'])->middleware('role_or_permission:Gestor de Caixa|criar pagamento|Supervisor|Operador Caixa|listar pagamento');
    Route::get('/pagamentos/excel', [PagamentosController::class, 'excel'])->middleware('role_or_permission:Gestor de Caixa|criar pagamento|Supervisor|Operador Caixa|listar pagamento');
    Route::get('/pagamentos/{id}/detalhes', [PagamentosController::class, 'detalhes'])->middleware('role_or_permission:Gestor de Caixa|Supervisor|Operador Caixa|criar pagamento|listar pagamento');
    Route::get('/pagamentos/{id}/invalida', [PagamentosController::class, 'invalida'])->middleware('role_or_permission:Gestor de Caixa|Supervisor|Operador Caixa|criar pagamento|listar pagamento');

    Route::get('/relatorios/fecho-caixa/operador', [RelatorioController::class, 'fechoCaixaOperador'])->name('mc.fecho-caixa-operador.index');
    Route::get('/relatorios/fecho-caixa/operador/pdf', [RelatorioController::class, 'pdf'])->name('mc.fecho-caixa-operador.pdf');
    Route::get('/relatorios/fecho-caixa/operador/excel', [RelatorioController::class, 'excel'])->name('mc.fecho-caixa-operador.excel');
    Route::get('/relatorios/extrato-depositos', [RelatorioController::class, 'extratoDeposito'])->name('mc.extrato-depositos.index');
    Route::get('/relatorios/extrato-pagamentos', [RelatorioController::class, 'extratoPagamento'])->name('mc.extrato-pagamentos.index');
    Route::get('/pagamentos/imprmir/{id}/detalhes', [RelatorioController::class, 'extratoDetalhesPagamento']);
    Route::get('/relatorios/extrato-deposito/pdf', [RelatorioController::class, 'pdf_deposito']);
    Route::get('/relatorios/extrato-deposito/excel', [RelatorioController::class, 'excel_deposito']);

    /**SEARCH */
    Route::get('/pesquisar-estudante', [SearhController::class, 'search'])->name('mc.searh-estudante.index');

    Route::get('/pesquisar-inscricao', [SearhController::class, 'search_preinscricao'])->name('mc.searh-estudante.index.');

    Route::get('/dados-pagamentos', [SearhController::class, 'dadosPagamentos']);
    /** ROUTAS DAS FACTURAS */
    Route::get('/pagamentos-estudantes/todas-referencias-nao-pagas/{codigo_matricula}', [PagamentosController::class, 'getTodasReferencias'])->name('mc.todas-referencias-nao-pagas');

    Route::get('/pagamentos-estudantes/fatura-by-reference', [PagamentosController::class, 'faturaByReference'])->name('mc.fatura-by-reference');
    Route::post('/pagamentos-estudantes/pagamento/diversos/create/{codigo?}', [PagamentosController::class, 'salvarPagamentosDiversos'])->name('mc.pagamento-diversos-create');
    Route::post('/pagamentos-estudantes/fatura/diversos/create/{numero_matricula?}', [PagamentosController::class, 'faturaDiversos'])->name('mc.pagamento-fatura-diversos-create');
    Route::post('/pagamentos-preinscricao', [PagamentosController::class, 'pagamentosPreinscricao']);
    Route::get('/fatura/diversos/{factura_id}', [PagamentosController::class, 'imprimirFaturaDiversos']);
    Route::get('/fatura-recibo/inscricao/{factura_id}', [PagamentosController::class, 'pdfFatReciboIExameAcesso']);
    Route::get('/pagamentos-estudantes/propina/{id_user}', [SearhController::class, 'propina']);
    Route::get('/imprimir-factura-ticket/{factura_id}', [PagamentosController::class, 'FaturaTicket']);

    Route::get('/banco-formaPagamento', [SearhController::class, 'bancosFormaPagamento'])->name('mc.banco-formas-pagamento');
    Route::get('/aluno/{id_user}', [SearhController::class, 'pegaAluno']);
    Route::get('/saldo/{id_user}', [SearhController::class, 'pegaAluno']);
    Route::get('/get-ano-lectivo/{id_user}', [SearhController::class, 'pegaAnolectivo']);
    Route::get('/pega-anos-lectivos-estudante/{id_user}', [SearhController::class, 'anosLectivoEstudante']);
    Route::get('/pagamentos-estudantes/servicos/{id_ano_lectivo}/{id_user}', [SearhController::class, 'servicos']);
    Route::get('/candidato/{id_user}', [SearhController::class, 'Candidato']);
    Route::get('/pagamentos-estudantes/ultimo-mes/{id_user}', [SearhController::class, 'mesUltimo']);
    Route::get('/pagamentos-estudantes/prestacoes-por-ano/{id_ano_lectivo}/{id_user}', [SearhController::class, 'getPrestacoesPorAnoLectivo']);
    Route::get('/estudante/pegar-descricao-bolseiro', [SearhController::class, 'getDescricaoTiposAlunos']);
    Route::get('/estudante/pega-bolseiro/{id_user}', [SearhController::class, 'descontoBolsa']);
    Route::get('/pagamentos-estudantes/ultima-prestacao-por-ano/{id_ano_lectivo}/{id_user}', [SearhController::class, 'getUltimaPrestacaoPorAnoLectivo']);
    Route::get('/pagamentos-estudantes/primeira-prestacao-por-ano/{id_ano_lectivo}/{id_user}', [SearhController::class, 'getPrimeiraPrestacaoPorAnoLectivo']);
    Route::get('/estudante/pega-finalista/{id_user}', [SearhController::class, 'finalista']);
    Route::get('/estudante/referencias-nao-pagas/{id_user}', [SearhController::class, 'getTodasReferencias']);
    Route::get('/estudante/verifica-confirmacao-no-ano-corrente/{id_user}', [SearhController::class, 'verificaConfirmacaoNoAnoLectivoCorrente']);
    Route::get('/estudante/prestacoes-por-bolsa-semestre',  [SearhController::class, 'prestacoesPorBolsaSemestre']);
    Route::get('/ciclos',  [SearhController::class, 'ciclos']);
    Route::get('/ano-lectivo-actual',  [SearhController::class, 'anoLectivoActual']);
    Route::get('/saldo/{id}', 'PagamentosEstudanteController@saldo');
    Route::get('/operacoes/caixas', [CaixaController::class, 'index']); //->middleware('role_or_permission:Gestor de Caixa|criar caixa|listar caixa');
    Route::post('/operacoes/caixas/store', [CaixaController::class, 'store']); //->middleware('role_or_permission:Gestor de Caixa|criar caixa|listar caixa');
    Route::post('/operacoes/caixas/update', [CaixaController::class, 'update']); //->middleware('role_or_permission:Gestor de Caixa|criar caixa|listar caixa');
    Route::get('/operacoes/caixas/show/{id}', [CaixaController::class, 'show']); //->middleware('role_or_permission:Gestor de Caixa|criar caixa|listar caixa');
    Route::get('/operacoes/caixas/delete/{id}', [CaixaController::class, 'destroy']); //->middleware('role_or_permission:Gestor de Caixa|criar caixa|listar caixa');
    Route::get('/roles/index', [RoleController::class, 'index']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    Route::post('/roles/store', [RoleController::class, 'store']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    Route::put('/roles/update/{id}', [RoleController::class, 'update']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    Route::post('/roles/adicionar-permissions', [RoleController::class, 'adicionar_permissions']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    Route::get('/roles/permissions/{id}', [RoleController::class, 'getPermissionsRole']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    Route::get('/roles/utilizadores', [RoleController::class, 'getUtilizadores']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    Route::post('/roles/utilizadores-roles', [RoleController::class, 'adicionar_perfil_utilizador']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    Route::get('/roles/utilizador-perfil/{id}', [RoleController::class, 'getPerfilUtilizador']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    Route::get('/roles/utilizador-remover-perfil/{id}', [RoleController::class, 'removerPerfilUtilizador']); //->middleware('role_or_permission:Gestor de Caixa|criar operador|listar operador');
    
    Route::get('/verificar-caixa-aberto',  [SearhController::class, 'verificarCaixaAberto']);
    
    Route::resource('notifications',  NotificationController::class)->except(['edit']);

});
