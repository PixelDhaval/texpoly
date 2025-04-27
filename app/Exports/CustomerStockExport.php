<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CustomerStockExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $packinglists;
    
    public function __construct($packinglists)
    {
        $this->packinglists = $packinglists;
    }

    public function collection()
    {
        return $this->packinglists;
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Category',
            'Section',
            'Label Name',
            'Stock',
            'Customer Qty'
        ];
    }

    public function map($row): array
    {
        return [
            $row->product->short_code,
            $row->product->name,
            $row->product->category->name,
            $row->product->subcategory->name ?? 'N/A',
            $row->label_name,
            $row->stock,
            $row->customer_qty
        ];
    }
}
