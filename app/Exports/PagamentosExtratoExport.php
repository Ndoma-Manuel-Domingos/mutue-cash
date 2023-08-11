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

class PagamentosExtratoExport implements FromCollection,
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
    public $codigo_matricula;
    

    public function __construct($request)
    {
        $this->data_inicio = $request->data_inicio;
        $this->data_final = $request->data_final;
        $this->operador = $request->operador;
        $this->ano_lectivo = $request->ano_lectivo;
        $this->codigo_matricula = $request->codigo_matricula;
    }

    public function headings():array
    {
        return[
            'Nº Factura',
            'Estudante',
            'Valor a pagar',
            'Valor pago',
            'Data da factura',
            'Saldo Restante',
        ];
    }

    public function map($item):array
    {
        return[
            $item->codigo_factura,
            $item->Nome_Completo,
            number_format($item->ValorAPagar ?? 0, 2, ',', '.'),
            number_format($item->valor_depositado ?? 0, 2, ',', '.'),
            date("Y-m-d", strtotime($item->DataRegisto)),
            number_format(0, 2, ',', '.'),
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
        
        if($this->data_inicio){
            $this->data_inicio = $this->data_inicio;
        }else{
            $this->data_inicio = date("Y-m-d");
        }   
        $data['items'] = Pagamento::when($this->data_inicio, function($query, $value){
            $query->where('DataRegisto', '>=' ,Carbon::parse($value) );
        })
        // ->when($this->data_final, function($query, $value){
        //     $query->where('DataRegisto', '<=' ,Carbon::parse($value));
        // })
        ->when($this->codigo_matricula, function($query, $value){
            dd($value);
            $query->where('factura.CodigoMatricula', $value);
        })
        ->leftjoin('factura', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
        ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
        ->where('forma_pagamento', 6)
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
            
                // Definir o título na célula A1
                $event->sheet->getDelegate()->mergeCells('A5', 'E5');
                $event->sheet->getDelegate()->setCellValue('A5', 'LISTA DE EXTRADOS DE PAGAMENTOS');
            
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
        $drawing->setDescription('ISTA');
        $drawing->setPath(public_path('/images/logotipo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');

        return $drawing;
    }


}
