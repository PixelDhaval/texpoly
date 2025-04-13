<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Packinglist;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TotalStockExport;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = [];
        $reportType = $request->get('report', '');

        switch ($reportType) {
            case 'daily-production':
                $data = $this->generateDailyProductionReport($request);
                break;
            case 'customer-stock':
                $data = $this->generateCustomerStockReport($request);
                break;
            case 'total-stock':
                $data = $this->generateTotalStockReport($request);
                if ($request->get('download') === 'excel') {
                    return Excel::download(
                        new TotalStockExport($data), 
                        'total_stock_' . now()->format('Y-m-d') . '.xlsx'
                    );
                }
                break;
        }

        return view('reports.index', compact('data', 'reportType'));
    }

    private function generateDailyProductionReport(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        // Slot time ranges
        $slots = [
            1 => ['start' => '00:00:00', 'end' => '10:30:00'],
            2 => ['start' => '10:30:01', 'end' => '13:15:00'],
            3 => ['start' => '13:15:01', 'end' => '15:30:00'],
            4 => ['start' => '15:30:01', 'end' => '23:59:59']
        ];

        // Get production bales
        $productionData = Bale::with(['packinglist.customer', 'packinglist.product'])
            ->whereDate('created_at', $date)
            ->where('type', 'production')
            ->get()
            ->groupBy('packinglist.customer_id');

        // Get repacking bales
        $repackingData = Bale::with([
                'packinglist.customer', 
                'packinglist.product',
                'refPackinglist.customer',
                'refPackinglist.product'
            ])
            ->whereDate('created_at', $date)
            ->where('type', 'repacking')
            ->get();

        // Process production data
        $customerTotals = [];
        $customerProducts = [];

        foreach ($productionData as $customerId => $bales) {
            $customer = $bales->first()->packinglist->customer;
            $customerTotals[$customer->name] = 0;
            $products = [];

            foreach ($bales->groupBy('packinglist.product_id') as $productBales) {
                $first = $productBales->first();
                $row = [
                    'product_code' => $first->packinglist->product->short_code,
                    'label_name' => $first->packinglist->label_name,
                    'slot1' => 0,
                    'slot2' => 0,
                    'slot3' => 0,
                    'slot4' => 0
                ];

                foreach ($productBales as $bale) {
                    $time = Carbon::parse($bale->created_at)->format('H:i:s');
                    foreach ($slots as $slotNum => $slotTime) {
                        if ($time >= $slotTime['start'] && $time <= $slotTime['end']) {
                            $row['slot' . $slotNum]++;
                            break;
                        }
                    }
                }

                $row['total'] = array_sum(array_slice($row, 2, 4));
                $customerTotals[$customer->name] += $row['total'];
                $products[] = $row;
            }

            $customerProducts[$customer->name] = $products;
        }

        return [
            'date' => $date,
            'customerTotals' => $customerTotals,
            'repackingData' => $repackingData,
            'customerProducts' => $customerProducts,
            'slots' => $slots
        ];
    }

    private function generateCustomerStockReport(Request $request)
    {
        $customers = Customer::all();
        $categories = Category::all();
        $subcategories = Subcategory::all();
        $packinglists = collect();

        if ($request->filled('customer')) {
            $query = Packinglist::with(['product.category', 'product.subcategory'])
                ->where('customer_id', $request->customer)
                ->where('stock', '>', 0)
                ->join('products', 'packinglists.product_id', '=', 'products.id');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('products.name', 'like', '%' . $search . '%')
                      ->orWhere('packinglists.label_name', 'like', '%' . $search . '%')
                      ->orWhere('products.short_code', 'like', '%' . $search . '%');
                });
            }

            if ($request->filled('category')) {
                $query->where('products.category_id', $request->category);
            }

            if ($request->filled('subcategory')) {
                $query->where('products.subcategory_id', $request->subcategory);
            }

            $packinglists = $query->orderBy('products.short_code')
                                 ->select('packinglists.*')
                                 ->get();
        }

        return [
            'customers' => $customers,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'packinglists' => $packinglists
        ];
    }

    private function generateTotalStockReport()
    {
        $customers = Customer::all();
        
        $stockData = Packinglist::with(['product.category', 'customer'])
            ->where('stock', '>', 0)
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->orderBy('products.short_code')
            ->get()
            ->groupBy('product_id');

        $customerTotals = [];
        foreach ($customers as $customer) {
            $customerTotals[$customer->id] = 0;
        }

        $rows = [];
        foreach ($stockData as $productId => $items) {
            $row = [
                'product' => [
                    'code' => $items->first()->product->short_code,
                    'name' => $items->first()->product->name,
                    'category' => $items->first()->product->category->name,
                ],
                'stocks' => [],
                'total' => 0
            ];

            foreach ($customers as $customer) {
                $stock = $items->firstWhere('customer_id', $customer->id)?->stock ?? 0;
                $row['stocks'][$customer->id] = $stock;
                $row['total'] += $stock;
                $customerTotals[$customer->id] += $stock;
            }

            $rows[] = $row;
        }

        return [
            'customers' => $customers,
            'rows' => $rows,
            'customerTotals' => $customerTotals,
            'grandTotal' => array_sum($customerTotals)
        ];
    }
}
