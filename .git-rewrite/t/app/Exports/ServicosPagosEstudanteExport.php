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

class ServicosPagosEstudanteExport implements FromCollection,
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
            'Serviço',
            'Valor',
            'Data Pag.Banco',
            'Data de Validação',
        ];
    }

    public function map($item):array
    {
        return[
            $item->items->servico->Descricao,
            number_format($item->Totalgeral, 2, ',', '.') . " KZ",
            $item->DataBanco,
            $item->updated_at,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $servicos = TipoServico::where('Descricao', 'not like', 'Propina %')
        ->where('codigo_ano_lectivo', $this->ano)
        ->pluck('Codigo');
        
        return Pagamento::where('Codigo_PreInscricao', $this->codigo)
        ->join('tb_pagamentosi', 'tb_pagamentos.Codigo', '=', 'tb_pagamentosi.Codigo_Pagamento')
        ->whereIn('Codigo_Servico', $servicos)
        ->where('AnoLectivo', $this->ano)
        ->with('items.servico')
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
