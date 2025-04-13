<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TotalStockExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];
        
        // Header row with customer names
        $headerRow = ['Product Code', 'Product Details', 'Total'];
        foreach ($this->data['customers'] as $customer) {
            $headerRow[] = $customer->name;
        }
        $rows[] = $headerRow;

        // Totals row
        $totalsRow = ['', '', $this->data['grandTotal']];
        foreach ($this->data['customers'] as $customer) {
            $totalsRow[] = $this->data['customerTotals'][$customer->id];
        }
        $rows[] = $totalsRow;

        // Data rows
        foreach ($this->data['rows'] as $row) {
            $dataRow = [
                $row['product']['code'],
                $row['product']['name'] . ' (' . $row['product']['category'] . ')',
                $row['total']
            ];
            
            foreach ($this->data['customers'] as $customer) {
                $dataRow[] = $row['stocks'][$customer->id];
            }
            
            $rows[] = $dataRow;
        }

        return $rows;
    }

    public function headings(): array
    {
        return []; // Headers are included in array()
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Header row
            2 => ['font' => ['bold' => true]], // Totals row
            'C' => ['alignment' => ['horizontal' => 'center']], // Total column
        ];
    }
}
