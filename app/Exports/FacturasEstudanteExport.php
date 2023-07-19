<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\Factura;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class FacturasEstudanteExport implements FromCollection,
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
            'Factura',
            'Estado',
            'Tipo',
            'Preço Total',
            'Valor Entregue',
            'Data',
        ];
    }

    public function map($item):array
    {
        return[
            $item->Codigo,
            $item->estado,
            $item->items->servico->Descricao,
            number_format($item->ValorAPagar, 2, ',', '.') . " KZ",
            number_format($item->ValorEntregue, 2, ',', '.') . " KZ",
            $item->DataFactura,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Factura::when($this->ano, function ($query, $value) {
            $query->where('ano_lectivo', $value);
        })
        ->where('estado', $this->estado)
        ->with('items.servico')
        ->where('CodigoMatricula', $this->codigo)
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
