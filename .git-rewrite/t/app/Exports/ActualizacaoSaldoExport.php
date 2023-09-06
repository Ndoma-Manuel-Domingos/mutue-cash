<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\ActualizarSaldoAluno;
use App\Models\AnoLectivo;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ActualizacaoSaldoExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $o, $di, $df;

    public function __construct($o, $di, $df)
    {
        $this->o = $o;
        $this->di = $di;
        $this->df = $df;
    }

    public function headings():array
    {
        return[
            'Estudante',
            'Data Actualização',
            'Saldo Anterior',
            'Saldo Actual',
            'Criado Por',
        ];
    }

    public function map($caixa):array
    {
        return[
            $caixa->aluno,
            $caixa->data_actualizacao,
            $caixa->saldo_anterior,
            $caixa->saldo_actual,
            $caixa->nome,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ActualizarSaldoAluno::when($this->o, function ($query, $value) {
            $query->where('mca_tb_utilizador.pk_utilizador', '=', $value);
        })
        ->when($this->di, function ($query, $value) {
            $query->whereDate('data_actualizacao', '>=', Carbon::createFromDate($value));
        })
        ->when($this->df, function ($query, $value) {
            $query->whereDate('data_actualizacao', '<=', Carbon::createFromDate($value));
        })
        ->join('tb_preinscricao', 'tb_actualizacao_saldo_aluno.aluno_id' ,'=','tb_preinscricao.Codigo')
        ->join('mca_tb_utilizador', DB::raw('json_extract(tb_actualizacao_saldo_aluno.ref_utilizador, "$.pk")') ,'=','mca_tb_utilizador.pk_utilizador')
        ->select(
            DB::raw('json_extract(tb_actualizacao_saldo_aluno.ref_utilizador, "$.desc") as nome'),
            'tb_preinscricao.Nome_Completo AS aluno',
            'tb_actualizacao_saldo_aluno.data_actualizacao',
            'tb_actualizacao_saldo_aluno.saldo_anterior',
            'tb_actualizacao_saldo_aluno.saldo_actual',
            'tb_actualizacao_saldo_aluno.obs',
            'tb_actualizacao_saldo_aluno.id',
            )
        ->get();
    }


    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event){
                $event->sheet->getStyle('A6:G6')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                            'color' => ['rgb' => '000000'],
                        ],
                    ]
                ]);

            }
        ];
    }

    public function startCell(): String
    {
        return "A6";
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('FECHO DO CAIXA');
        $drawing->setPath(public_path('/images/logotipo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');

        return $drawing;
    }


}
