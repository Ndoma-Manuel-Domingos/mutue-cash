<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\AnoLectivo;
use App\Models\Factura;
use App\Models\GradeCurricularAluno;
use App\Models\Pagamento;
use App\Models\TipoServico;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class EstudantePropinaPagaExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $a , $m , $f , $c , $t;

    public function __construct($a , $m , $f , $c , $t)
    {
        $this->a = $a;
        $this->m = $m;
        $this->f = $f;
        $this->c = $c;
        $this->t = $t;
    }

    public function headings():array
    {
        return[
            'Nº Matricula',
            'Nome',
            'Faculdade',
            'Curso',
            'Turno',
            'Mês/Parcela',
            // 'Ano Lectivo',
        ];
    }

    public function map($caixa):array
    {
        return[
            $caixa->matricula,
            $caixa->aluno,
            $caixa->faculdade,
            $caixa->curso,
            $caixa->turno,
            $caixa->servico,
            // $caixa->AnoLectivoPagamento,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // recuperar os servicos deste ano lectivo primeiramente mais somente servicos de propinas
        $servicos = TipoServico::where('Descricao', 'like', 'Propina %')->where('codigo_ano_lectivo', $this->a)->pluck('Codigo');
        
        $grade_curriculares = GradeCurricularAluno::when($this->a, function ($query, $value) {
            $query->where('codigo_ano_lectivo', '=', $value);
            $query->whereIn('Codigo_Status_Grade_Curricular', [2,3]);
        })->distinct('codigo_matricula')->pluck('codigo_matricula');
        
        return Pagamento::when($this->a, function ($query, $value) {
            $query->where('tb_pagamentos.AnoLectivo', '=', $value);
        })
        ->when($this->m, function ($query, $value) {
            $query->where('tb_pagamentosi.mes_temp_id', '=', $value);
        })
        ->when($this->f, function ($query, $value) {
            $query->where('tb_cursos.faculdade_id', '=', $value);
        })
        ->when($this->c, function ($query, $value) {
            $query->where('tb_cursos.Codigo', '=', $value);
        })
        ->when($this->t, function ($query, $value) {
            $query->where('tb_periodos.Codigo', '=', $value);
        })
        ->join('tb_pagamentosi', 'tb_pagamentos.Codigo', '=', 'tb_pagamentosi.Codigo_Pagamento')
        ->join('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
        ->join('mes_temp', 'tb_pagamentosi.mes_temp_id', '=', 'mes_temp.id')
        ->join('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
        ->join('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
        ->join('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->join('tb_faculdade', 'tb_cursos.faculdade_id', '=', 'tb_faculdade.codigo')
        ->join('tb_periodos', 'tb_preinscricao.Codigo_Turno', '=', 'tb_periodos.Codigo')
        ->where('tb_pagamentos.estado', 1)
        ->whereIn('tb_matriculas.Codigo', $grade_curriculares)
        ->whereIn('tb_pagamentosi.Codigo_Servico', $servicos)
        ->select('tb_matriculas.Codigo AS matricula',
            'tb_preinscricao.Nome_Completo AS aluno',
            'tb_cursos.Designacao AS curso',
            'tb_periodos.Designacao AS turno',
            'mes_temp.designacao AS servico',
            'mes_temp.id AS IdServico',
            'tb_faculdade.designacao AS faculdade',
            'tb_pagamentos.Totalgeral AS valores'
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
