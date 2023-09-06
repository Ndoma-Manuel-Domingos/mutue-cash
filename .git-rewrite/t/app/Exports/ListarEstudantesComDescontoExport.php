<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\AnoLectivo;
use App\Models\Bolseiro;
use App\Models\DescontoAluno;
use App\Models\Matricula;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ListarEstudantesComDescontoExport implements FromCollection, 
    WithHeadings, 
    ShouldAutoSize, 
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $tipo_instituicao, $instituicao, $tipo_desconto;

    public function __construct($tipo_instituicao, $instituicao, $tipo_desconto)
    {
        $this->tipo_instituicao = $tipo_instituicao;
        $this->instituicao = $instituicao;
        $this->tipo_desconto = $tipo_desconto;
    }

    public function headings():array
    {
        return[
            'Matricula',
            'Nome',
            'Instituições',
            'Tipo de Desconto',
            'Estado',
            'Semestre',
        ];
    }

    public function map($caixa):array
    {
        return[
            $caixa->codigo_matricula,
            $caixa->nome,
            $caixa->instituicao,
            $caixa->tipoDesconto,
            $caixa->Designacao,
            $caixa->semestreItem,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return DescontoAluno::when($this->instituicao, function ($query, $value) {
            $query->where('tb_descontos_alunoo.instituicao_id', $value);
        })
        ->when($this->tipo_desconto, function ($query, $value) {
            $query->where('tb_descontos_alunoo.codigo_tipo_desconto', $value);
        })
        ->when($this->tipo_instituicao, function($query, $value){
            $query->where('tb_Instituicao.tipo_instituicao', $value);
        })
        ->join('tb_matriculas', 'tb_descontos_alunoo.codigo_matricula', '=', 'tb_matriculas.Codigo')
        ->join('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->join('tb_admissao', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
        ->join('tb_preinscricao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
        ->join('tb_tipo_descontos', 'tb_descontos_alunoo.codigo_tipo_desconto', '=', 'tb_tipo_descontos.Codigo')
        ->join('tb_Instituicao', 'tb_descontos_alunoo.instituicao_id', '=', 'tb_Instituicao.codigo')
        ->join('tb_status', 'tb_descontos_alunoo.estatus_desconto_id', '=', 'tb_status.Codigo')
        ->join('tb_semestres', 'tb_descontos_alunoo.semestre', '=', 'tb_semestres.Codigo')
         ->select('tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_descontos_alunoo.codigo_matricula', 'tb_descontos_alunoo.codigo', 'tb_tipo_descontos.designacao AS tipoDesconto', 'tb_semestres.Designacao AS semestreItem', 'tb_preinscricao.Nome_Completo As nome', 'tb_descontos_alunoo.codigo_anoLectivo',
            'tb_descontos_alunoo.instituicao_id',
            'tb_Instituicao.instituicao',
            'tb_descontos_alunoo.semestre',
            'tb_descontos_alunoo.afectacao',
            'tb_descontos_alunoo.codigo_tipo_desconto',
            'tb_preinscricao.Codigo AS preninscricaoCodigo',
            'tb_status.Designacao'
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
