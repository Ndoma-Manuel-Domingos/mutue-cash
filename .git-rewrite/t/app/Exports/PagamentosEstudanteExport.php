<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\Pagamento;
use App\Models\TipoServico;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PagamentosEstudanteExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $codigo, $estado, $ano;

    public function __construct($request)
    {
        $this->codigo = $request->codigo;
        $this->estado = $request->estado;
        $this->ano = $request->ano;
    }

    public function headings():array
    {
        return[
            'Recibo',
            'Serviço',
            'Estado',
            'Data Banco',
            'Data Registro',
            'Factura',
            'Valor',
        ];
    }

    public function map($item):array
    {
        return[
            $item->Codigo,
            $item->Descricao,
            $item->estado,
            $item->DataBanco,
            $item->Data,
            $item->codigo_factura,
            number_format($item->valor_depositado, 2, ',', '.') . " KZ",
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $servicos = TipoServico::where('Descricao', 'like', 'Propina %')->where('codigo_ano_lectivo', $this->ano)->pluck('Codigo');
        
        return Pagamento::when($this->ano, function ($query, $value) {
            $query->where('AnoLectivo', $value);
        })
        ->leftjoin('tb_pagamentosi', 'tb_pagamentos.Codigo', '=', 'tb_pagamentosi.Codigo_Pagamento')
        ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
        ->whereIn('Codigo_Servico', $servicos)
        ->where('Codigo_PreInscricao', $this->codigo)
        ->where('tb_pagamentos.estado', $this->estado)
        ->select('tb_tipo_servicos.Descricao', 'tb_pagamentos.Codigo', 'tb_pagamentos.estado', 'tb_pagamentos.DataBanco', 'tb_pagamentos.Data', 'tb_pagamentos.codigo_factura', 'tb_pagamentos.valor_depositado')
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
