<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\AnoLectivo;
use App\Models\Deposito;
use App\Models\Pagamento;
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

class PagamentosExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $data_inicio;
    public $data_final;
    public $operador;
    public $ano_lectivo;
    

    public function __construct($request)
    {
        $this->data_inicio = $request->data_inicio;
        $this->data_final = $request->data_final;
        $this->operador = $request->operador;
        $this->ano_lectivo = $request->ano_lectivo;
    }

    public function headings():array
    {
        return[
            'Nº',
            'Matricula',
            'Serviço Pago',
            'Nome',
            'Curso',
            'Data',
            'Total Pago',
        ];
    }

    public function map($item):array
    {
        return[
            $item->Codigo,
            $item->matricula,
            $item->servico,
            $item->Nome_Completo,
            $item->curso,
            date("Y-m-d", strtotime($item->DataRegisto)),
            number_format($item->Totalgeral ?? 0, 2, ',', '.'),
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
    
        $ano = AnoLectivo::where('status', '1')->first();
        
        if(!$this->ano_lectivo){
            $this->ano_lectivo = $ano->Codigo;
        }
        
        $data['items'] = Pagamento::when($this->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })->when($this->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })->when($this->operador, function($query, $value){
            $query->where('fk_utilizador', $value);
        })->when($this->ano_lectivo, function($query, $value){
            $query->where('tb_pagamentos.AnoLectivo', $value);
        })
        ->where('forma_pagamento', 6)
        ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
        ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
        ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
        ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
        ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
        ->orderBy('tb_pagamentos.Codigo', 'desc')
        ->select('tb_pagamentos.Codigo', 'Nome_Completo', 'Totalgeral', 'DataRegisto', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_tipo_servicos.Descricao AS servico')
        ->get();
        
        return $data['items'];
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
        $drawing->setDescription('LISTA DE DEPOSITOS');
        $drawing->setPath(public_path('/images/logotipo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');

        return $drawing;
    }


}
