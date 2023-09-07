<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\AnoLectivo;
use App\Models\Bolseiro;
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

class ListarEstudantesCreditoEducacionalExport implements FromCollection, 
    WithHeadings, 
    ShouldAutoSize, 
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $tipo_instituicao, $instituicao, $bolsa, $desconto;

    public function __construct($tipo_instituicao, $instituicao, $bolsa, $desconto)
    {
        $this->tipo_instituicao = $tipo_instituicao;
        $this->instituicao = $instituicao;
        $this->bolsa = $bolsa;
        $this->desconto = $desconto;
    }

    public function headings():array
    {
        return[
            'Matricula',
            'Nome',
            'Instituições',
            'Tipo de Bolsa',
            'Desconto',
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
            $caixa->tipobolsa,
            $caixa->desconto,
            $caixa->status,
            $caixa->semestreItem,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Bolseiro::when($this->instituicao, function ($query, $value) {
            $query->where('tb_bolseiros.codigo_Instituicao', $value);
        })
        ->when($this->bolsa, function ($query, $value) {
            $query->where('tb_bolseiros.codigo_tipo_bolsa', $value);
        })
        ->when($this->desconto, function ($query, $value) {
            $query->where('tb_bolseiros.desconto', $value);
        })
        ->when($this->tipo_instituicao, function($query, $value){
            $query->where('tb_Instituicao.tipo_instituicao', $value);
        })
        ->join('tb_matriculas', 'tb_bolseiros.codigo_matricula', '=', 'tb_matriculas.Codigo')
        ->join('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->join('tb_admissao', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
        ->join('tb_preinscricao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
        ->join('tb_tipo_bolsas', 'tb_bolseiros.codigo_tipo_bolsa', '=', 'tb_tipo_bolsas.codigo')
        ->join('tb_Instituicao', 'tb_bolseiros.codigo_Instituicao', '=', 'tb_Instituicao.codigo')
        ->join('tb_semestres', 'tb_bolseiros.semestre', '=', 'tb_semestres.Codigo')
         ->select('tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_bolseiros.codigo_matricula', 'tb_bolseiros.codigo', 'tb_tipo_bolsas.designacao AS tipobolsa', 
            'tb_bolseiros.desconto', 'tb_bolseiros.status', 'tb_semestres.Designacao AS semestreItem', 'tb_preinscricao.Nome_Completo As nome', 'tb_bolseiros.codigo_anoLectivo',
            'tb_bolseiros.codigo_Instituicao',
            'tb_Instituicao.instituicao',
            'tb_bolseiros.semestre',
            'tb_bolseiros.afectacao',
            'tb_bolseiros.codigo_tipo_bolsa',
            'tb_bolseiros.desconto',
            'tb_bolseiros.status', 
            'tb_preinscricao.Codigo AS preninscricaoCodigo'
        )
        ->limit(100)
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
