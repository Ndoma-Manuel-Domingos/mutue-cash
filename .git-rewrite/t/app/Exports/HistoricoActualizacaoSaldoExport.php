<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\ActualizarSaldoAluno;
use App\Models\AnoLectivo;
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

class HistoricoActualizacaoSaldoExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $codigo;

    public function __construct($request)
    {
        $this->codigo = $request->codigo;
    }

    public function headings():array
    {
        return[
            'Data Actualização',
            'Saldo Anterior',
            'Saldo Actual',
            'Criado Por',
            'Nome Aluno',
        ];
    }

    public function map($item):array
    {
        return[
            $item->data_actualizacao,
            number_format($item->saldo_anterior, 2, ',', '.'),
            number_format($item->saldo_actual, 2, ',', '.'),
            $item->nome,
            $item->Nome_Completo,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ActualizarSaldoAluno::where('aluno_id', $this->codigo)
        ->join('mca_tb_utilizador', DB::raw('json_extract(tb_actualizacao_saldo_aluno.ref_utilizador, "$.pk")'), '=', 'mca_tb_utilizador.codigo_importado')
        ->select(
            DB::raw('json_extract(tb_actualizacao_saldo_aluno.ref_utilizador, "$.desc") as nome'),
            'tb_actualizacao_saldo_aluno.data_actualizacao',
            'tb_actualizacao_saldo_aluno.saldo_anterior',
            'tb_actualizacao_saldo_aluno.saldo_actual',
            'tb_actualizacao_saldo_aluno.obs',
            'tb_actualizacao_saldo_aluno.id',
            'tb_preinscricao.Nome_Completo',
            )
            ->leftjoin('tb_preinscricao', 'tb_actualizacao_saldo_aluno.aluno_id', '=', 'tb_preinscricao.Codigo')
        ->orderBy('tb_actualizacao_saldo_aluno.id', 'desc')
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
