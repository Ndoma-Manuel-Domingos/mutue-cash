<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositoController;
use App\Http\Controllers\PagamentosController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\SearhController;
use App\Models\Deposito;
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

Route::get('/', [AuthController::class, 'login'])
    ->middleware('guest');

Route::get('/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('login');

Route::post('/login', [AuthController::class, 'autenticacao'])->name('mc.login.post');

Route::group(["middleware" => "auth"], function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('mc.logout');

    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('mc.dashboard');

    Route::get('/depositos', [DepositoController::class, 'index'])->name('mc.depositos.index');
    Route::post('/depositos/store', [DepositoController::class, 'store'])->name('mc.depositos.store');
    Route::get('/depositos/pdf', [DepositoController::class, 'pdf']);
    Route::get('/depositos/excel', [DepositoController::class, 'excel']);
    Route::get('/depositos/imprimir-comprovativo', [DepositoController::class, 'imprimir']);
    
    Route::get('/pagamentos', [PagamentosController::class, 'index'])->name('mc.pagamentos.index');
    Route::get('/pagamentos/criar', [PagamentosController::class, 'create'])->name('mc.pagamentos.create');
    Route::get('/pagamentos/pdf', [PagamentosController::class, 'pdf']);
    Route::get('/pagamentos/excel', [PagamentosController::class, 'excel']);
    Route::get('/pagamentos/{id}/detalhes', [PagamentosController::class, 'detalhes']);

    Route::get('/relatorios/fecho-caixa/operador', [RelatorioController::class, 'fechoCaixaOperador'])->name('mc.fecho-caixa-operador.index');
    Route::get('/relatorios/fecho-caixa/operador/pdf', [RelatorioController::class, 'pdf'])->name('mc.fecho-caixa-operador.pdf');
    Route::get('/relatorios/fecho-caixa/operador/excel', [RelatorioController::class, 'excel'])->name('mc.fecho-caixa-operador.excel');

    /**SEARCH */
    Route::get('/pesquisar-estudante', [SearhController::class, 'search'])->name('mc.searh-estudante.index');

    /** ROUTAS DAS FACTURAS */
    Route::get('/pagamentos-estudantes/todas-referencias-nao-pagas/{codigo_matricula}', [PagamentosController::class, 'getTodasReferencias'])->name('mc.todas-referencias-nao-pagas');

    Route::get('/pagamentos-estudantes/fatura-by-reference', [PagamentosController::class, 'faturaByReference'])->name('mc.fatura-by-reference');

    Route::post('/pagamentos-estudantes/pagamento/diversos/create/{codigo?}', [PagamentosController::class, 'salvarPagamentosDiversos'])->name('mc.pagamento-diversos-create');
    Route::post('/pagamentos-estudantes/fatura/diversos/create/{numero_matricula?}', [PagamentosController::class, 'faturaDiversos'])->name('mc.pagamento-fatura-diversos-create');
    
    Route::get('/fatura/diversos/{factura_id}', [PagamentosController::class, 'imprimirFaturaDiversos']);
    
    Route::get('/pagamentos-estudantes/propina/{id_user}', [SearhController::class, 'propina']);


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
});
