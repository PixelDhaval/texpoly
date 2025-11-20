<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerBalanceExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $balances;
    protected $fromDate;
    protected $toDate;

    public function __construct($balances, $fromDate, $toDate)
    {
        $this->balances = $balances;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection()
    {
        $data = collect();
        
        $totalOpeningBalance = 0;
        $totalProduction = 0;
        $totalRepackingIn = 0;
        $totalRepackingOut = 0;
        $totalTransferIn = 0;
        $totalTransferOut = 0;
        $totalInward = 0;
        $totalOutward = 0;
        $totalCutting = 0;
        $totalDispatch = 0;
        $totalClosingBalance = 0;

        foreach ($this->balances as $customer) {
            $data->push([
                'party_name' => $customer->name,
                'opening_balance' => $customer->opening_balance,
                'production' => $customer->production,
                'repacking_in' => $customer->repacking_in,
                'repacking_out' => $customer->repacking_out,
                'transfer_in' => $customer->transfer_in,
                'transfer_out' => $customer->transfer_out,
                'inward' => $customer->inward,
                'outward' => $customer->outward,
                'cutting' => $customer->cutting,
                'dispatch' => $customer->dispatch,
                'closing_balance' => $customer->closing_balance,
            ]);

            $totalOpeningBalance += $customer->opening_balance;
            $totalProduction += $customer->production;
            $totalRepackingIn += $customer->repacking_in;
            $totalRepackingOut += $customer->repacking_out;
            $totalTransferIn += $customer->transfer_in;
            $totalTransferOut += $customer->transfer_out;
            $totalInward += $customer->inward;
            $totalOutward += $customer->outward;
            $totalCutting += $customer->cutting;
            $totalDispatch += $customer->dispatch;
            $totalClosingBalance += $customer->closing_balance;
        }

        // Add grand total row
        $data->push([
            'party_name' => 'Grand Total',
            'opening_balance' => $totalOpeningBalance,
            'production' => $totalProduction,
            'repacking_in' => $totalRepackingIn,
            'repacking_out' => $totalRepackingOut,
            'transfer_in' => $totalTransferIn,
            'transfer_out' => $totalTransferOut,
            'inward' => $totalInward,
            'outward' => $totalOutward,
            'cutting' => $totalCutting,
            'dispatch' => $totalDispatch,
            'closing_balance' => $totalClosingBalance,
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            'Party Name',
            'Opening Balance',
            'Production',
            'Repacking In',
            'Repacking Out',
            'Transfer In',
            'Transfer Out',
            'Inward',
            'Outward',
            'Cutting',
            'Dispatch',
            'Closing Balance',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        
        return [
            // Style the header row
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
            // Style the grand total row
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1D5DB']
                ]
            ],
        ];
    }

    public function title(): string
    {
        return 'Customer Balance ' . $this->fromDate . ' to ' . $this->toDate;
    }
}
