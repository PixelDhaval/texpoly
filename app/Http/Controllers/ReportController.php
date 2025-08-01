<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Packinglist;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TotalStockExport;
use App\Exports\CustomerStockExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                $response = $this->generateCustomerStockReport($request);
                // Check if response is an Excel download
                if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
                    return $response;
                }
                $data = $response;
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
            case 'grade-wise':
                $data = $this->generateGradeWiseReport($request);
                break;
            case 'product-wise-daily':
                $data = $this->generateProductWiseDailyReport($request);
                break;
        }

        return view('reports.index', compact('data', 'reportType'));
    }

    private function generateDailyProductionReport(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        // Filters
        $search = $request->get('search');
        $category = $request->get('category');
        $subcategory = $request->get('subcategory');
        $type = $request->get('type');

        // Define time slots using Carbon
        $slots = [
            1 => [
                'start' => Carbon::parse($date)->setTime(0, 0, 0),
                'end' => Carbon::parse($date)->setTime(10, 30, 59)
            ],
            2 => [
                'start' => Carbon::parse($date)->setTime(10, 31, 0),
                'end' => Carbon::parse($date)->setTime(13, 15, 59)
            ],
            3 => [
                'start' => Carbon::parse($date)->setTime(13, 16, 0),
                'end' => Carbon::parse($date)->setTime(15, 30, 59)
            ],
            4 => [
                'start' => Carbon::parse($date)->setTime(15, 31, 0),
                'end' => Carbon::parse($date)->setTime(23, 59, 59)
            ]
        ];

        // Fetch production data with filters
        $productionQuery = Bale::with(['packinglist.customer', 'packinglist.product'])
            ->join('packinglists', 'bales.packinglist_id', '=', 'packinglists.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->select([
                'bales.*',
                DB::raw('bales.created_at as actual_created_at')
            ])
            ->whereDate('bales.created_at', $date)
            ->where('bales.type', 'production');

        if ($search) {
            $search = strtoupper($search);
            $productionQuery->where(function($q) use ($search) {
                $q->where('products.name', 'like', '%' . $search . '%')
                  ->orWhere('products.short_code', 'like', '%' . $search . '%');
            });
        }
        if ($category) {
            $productionQuery->where('products.category_id', $category);
        }
        if ($subcategory) {
            $productionQuery->where('products.subcategory_id', $subcategory);
        }
        if ($type) {
            $productionQuery->where('products.type', $type);
        }

        $productionData = $productionQuery->orderBy('products.name')
            ->get()
            ->groupBy('packinglist.customer_id');

        // Fetch repacking data with same filters
        $repackingQuery = Bale::with([
                'packinglist.customer',
                'packinglist.product',
                'refPackinglist.customer',
                'refPackinglist.product'
            ])
            ->join('packinglists', 'bales.packinglist_id', '=', 'packinglists.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->whereDate('bales.created_at', $date)
            ->where('bales.type', 'repacking');

        if ($search) {
            $repackingQuery->where(function($q) use ($search) {
                $q->where('products.name', 'like', '%' . $search . '%')
                  ->orWhere('products.short_code', 'like', '%' . $search . '%');
            });
        }
        if ($category) {
            $repackingQuery->where('products.category_id', $category);
        }
        if ($subcategory) {
            $repackingQuery->where('products.subcategory_id', $subcategory);
        }
        if ($type) {
            $repackingQuery->where('products.type', $type);
        }

        $repackingData = $repackingQuery->orderBy('products.name')->get();

        // Prepare report data
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
                    'product_name' => $first->packinglist->product->name,
                    'label_name'   => $first->packinglist->label_name,
                    'slot1' => 0,
                    'slot2' => 0,
                    'slot3' => 0,
                    'slot4' => 0,
                ];

                foreach ($productBales as $bale) {
                    $baleTime = Carbon::parse($bale->actual_created_at);
                    
                    // Add debug logging
                    Log::info("Processing bale", [
                        'bale_id' => $bale->id,
                        'raw_timestamp' => $bale->actual_created_at,
                        'parsed_time' => $baleTime->format('Y-m-d H:i:s')
                    ]);
                    
                    // Use Carbon's between() for more accurate time slot checking
                    if ($baleTime->between($slots[1]['start'], $slots[1]['end'])) {
                        $row['slot1']++;
                    } elseif ($baleTime->between($slots[2]['start'], $slots[2]['end'])) {
                        $row['slot2']++;
                    } elseif ($baleTime->between($slots[3]['start'], $slots[3]['end'])) {
                        $row['slot3']++;
                    } else  {
                        $row['slot4']++;
                    }
                }

                $row['total'] = $row['slot1'] + $row['slot2'] + $row['slot3'] + $row['slot4'];
                $customerTotals[$customer->name] += $row['total'];
                $products[] = $row;
            }

            $customerProducts[$customer->name] = $products;
        }

        // Add categories and subcategories for filter dropdowns
        $categories = Category::all();
        $subcategories = Subcategory::all();

        return [
            'date' => $date,
            'customerTotals' => $customerTotals,
            'repackingData' => $repackingData,
            'customerProducts' => $customerProducts,
            'slots' => $slots,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'subcategory' => $subcategory,
                'type' => $type,
            ]
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
                $search = strtoupper($request->search);
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

            if ($request->filled('type')) {
                $query->where('products.type', $request->type);
            }

            $packinglists = $query->orderBy('products.name')
                                 ->select('packinglists.*')
                                 ->get();

            // Handle Excel download separately
            if ($request->get('download') === 'excel') {
                $customer = Customer::find($request->customer);
                $fileName = str_replace(' ', '_', strtolower($customer->name)) . '_' . now()->format('Y-m-d') . '.xlsx';
                return Excel::download(new CustomerStockExport($packinglists), $fileName);
            }
        }

        // Return data for view
        return [
            'customers' => $customers,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'packinglists' => $packinglists
        ];
    }

    private function generateTotalStockReport(Request $request)
    {
        $customers = Customer::all();
        $categories = Category::all();
        $subcategories = Subcategory::all();

        $query = Packinglist::with(['product.category', 'product.subcategory', 'customer'])
            ->where('stock', '>', 0)
            ->join('products', 'packinglists.product_id', '=', 'products.id');

        // Product name or code filter
        if ($request->filled('search')) {
            $search = strtoupper($request->search);
            $query->where(function($q) use ($search) {
                $q->where('products.name', 'like', '%' . $search . '%')
                  ->orWhere('products.short_code', 'like', '%' . $search . '%');
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->where('products.category_id', $request->category);
        }

        // Subcategory (section) filter
        if ($request->filled('subcategory')) {
            $query->where('products.subcategory_id', $request->subcategory);
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('products.type', $request->type);
        }

        $stockData = $query->orderBy('products.name')
            ->select('packinglists.*')
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
            'categories' => $categories,
            'subcategories' => $subcategories,
            'rows' => $rows,
            'customerTotals' => $customerTotals,
            'grandTotal' => array_sum($customerTotals)
        ];
    }

    private function generateGradeWiseReport(Request $request)
    {
        $fromDate = $request->get('from_date', now()->format('Y-m-d'));
        $toDate = $request->get('to_date', now()->format('Y-m-d'));

        // Get production counts by product grade
        $gradeData = Bale::with(['packinglist.product'])
            ->join('packinglists', 'bales.packinglist_id', '=', 'packinglists.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->select('products.grade', DB::raw('count(*) as count'))
            ->whereDate('bales.created_at', '>=', $fromDate)
            ->whereDate('bales.created_at', '<=', $toDate)
            ->where('bales.type', 'production')
            ->groupBy('products.grade')
            ->orderBy('products.grade')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->grade ?: 'No Grade' => $item->count];
            });

        // Get customer-wise breakdown by product grade
        $customerGradeData = Bale::with(['packinglist.customer', 'packinglist.product'])
            ->join('packinglists', 'bales.packinglist_id', '=', 'packinglists.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->join('customers', 'packinglists.customer_id', '=', 'customers.id')
            ->select(
                'customers.id as customer_id',
                'customers.name as customer_name',
                'products.grade',
                DB::raw('count(*) as bale_count')
            )
            ->whereDate('bales.created_at', '>=', $fromDate)
            ->whereDate('bales.created_at', '<=', $toDate)
            ->where('bales.type', 'production')
            ->groupBy('customers.id', 'customers.name', 'products.grade')
            ->orderBy('customers.name')
            ->orderBy('products.grade')
            ->get();

        // Format data for easier use in views
        $customerGradeSummary = [];
        foreach ($customerGradeData as $item) {
            $grade = $item->grade ?: 'No Grade';
            $customerId = $item->customer_id;
            $customerName = $item->customer_name;
            
            if (!isset($customerGradeSummary[$customerId])) {
                $customerGradeSummary[$customerId] = [
                    'name' => $customerName,
                    'grades' => [],
                    'total' => 0
                ];
            }
            
            $customerGradeSummary[$customerId]['grades'][$grade] = $item->bale_count;
            $customerGradeSummary[$customerId]['total'] += $item->bale_count;
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'gradeData' => $gradeData,
            'customerGradeData' => $customerGradeSummary,
            'allGrades' => $gradeData->keys()->toArray()
        ];
    }

    private function generateProductWiseDailyReport(Request $request)
    {
        $from_date = $request->get('from_date', now()->format('Y-m-d'));
        $to_date = $request->get('to_date', now()->format('Y-m-d'));

        // Define time slots
        $slots = [
            1 => [
                'start' => Carbon::parse($from_date)->setTime(0, 0, 0),
                'end' => Carbon::parse($to_date)->setTime(10, 30, 59)
            ],
            2 => [
                'start' => Carbon::parse($from_date)->setTime(10, 31, 0),
                'end' => Carbon::parse($to_date)->setTime(13, 15, 59)
            ],
            3 => [
                'start' => Carbon::parse($from_date)->setTime(13, 16, 0),
                'end' => Carbon::parse($to_date)->setTime(15, 30, 59)
            ],
            4 => [
                'start' => Carbon::parse($from_date)->setTime(15, 31, 0),
                'end' => Carbon::parse($to_date)->setTime(23, 59, 59)
            ]
        ];

        // Modify the production data query to include category and subcategory
        $productionData = Bale::with([
                'packinglist.product.category',
                'packinglist.product.subcategory',
                'packinglist.customer'
            ])
            ->join('packinglists', 'bales.packinglist_id', '=', 'packinglists.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->select([
                'bales.*',
                'products.id as product_id',
                'products.name as product_name',
                'products.short_code as product_code',
                DB::raw('bales.created_at as actual_created_at')
            ])
            ->whereDate('bales.created_at', '>=', $from_date)
            ->whereDate('bales.created_at', '<=', $to_date)
            ->where('bales.type', 'production')
            ->orderBy('products.name')
            ->get()
            ->groupBy('product_id');

        // Process data for report
        $productSummary = [];
        $slotTotals = [0, 0, 0, 0];
        $grandTotal = 0;

        foreach ($productionData as $productId => $bales) {
            $first = $bales->first();
            $row = [
                'product_code' => $first->product_code,
                'product_name' => $first->product_name,
                'category' => $first->packinglist->product->category->name ?? 'Uncategorized',
                'subcategory' => $first->packinglist->product->subcategory->name ?? 'None',
                'slot1' => 0,
                'slot2' => 0,
                'slot3' => 0,
                'slot4' => 0,
                'total' => 0,
                'customers' => []
            ];

            foreach ($bales as $bale) {
                $baleTime = Carbon::parse($bale->actual_created_at);
                $customerName = $bale->packinglist->customer->name;

                // Initialize customer data if not exists
                if (!isset($row['customers'][$customerName])) {
                    $row['customers'][$customerName] = 0;
                }
                $row['customers'][$customerName]++;

                // Increment slot counters
                if ($baleTime->between($slots[1]['start'], $slots[1]['end'])) {
                    $row['slot1']++;
                    $slotTotals[0]++;
                } elseif ($baleTime->between($slots[2]['start'], $slots[2]['end'])) {
                    $row['slot2']++;
                    $slotTotals[1]++;
                } elseif ($baleTime->between($slots[3]['start'], $slots[3]['end'])) {
                    $row['slot3']++;
                    $slotTotals[2]++;
                } else {
                    $row['slot4']++;
                    $slotTotals[3]++;
                }
            }

            $row['total'] = $row['slot1'] + $row['slot2'] + $row['slot3'] + $row['slot4'];
            $grandTotal += $row['total'];
            $productSummary[] = $row;
        }

        return [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'products' => $productSummary,
            'slotTotals' => $slotTotals,
            'grandTotal' => $grandTotal,
            'slots' => $slots
        ];
    }
}
