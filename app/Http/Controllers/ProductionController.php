<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Employee;
use App\Models\Orderlist;
use App\Models\Bale;
use App\Models\CancelBale;
use App\Models\Packinglist;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        $todayCount = Bale::whereDate('created_at', $today)
            ->where('type', 'production')->count();
        $yesterdayCount = Bale::whereDate('created_at', $yesterday)
            ->where('type', 'production')->count();
        $cancelCount = CancelBale::whereDate('created_at', $today)->get()->count();
        $repackCount = Bale::whereDate('created_at', $today)
            ->where('type', 'repacking')->count();
        $yesterdayRepackCount = Bale::whereDate('created_at', $yesterday)
            ->where('type', 'repacking')->count();

        $products = Product::orderBy('short_code', 'asc')->get();
        $employees = Employee::orderBy('name', 'asc')->get();
        
        $customerOrders = collect();
        if ($request->filled('product_id')) {
            $customerOrders = Orderlist::with(['packinglist.customer', 'order'])
                ->whereHas('order', function ($q) {
                    $q->where('status', 'production');
                })
                ->whereHas('packinglist', function ($q) use ($request) {
                    $q->where('product_id', $request->product_id)
                        ->where('customer_qty', '>', 0);
                })
                ->get()
                ->groupBy('packinglist.customer_id')
                ->map(function ($orders) {
                    $firstOrder = $orders->first();

                    // Separate pending and completed orders
                    $pendingOrders = $orders->filter(function ($order) {
                        $totalCustomerQty = $order->packinglist->customer_qty;
                        $currentStock = $order->packinglist->stock;
                        return $totalCustomerQty > $currentStock; // Pending if customer quantity exceeds stock
                    });

                    $completedOrders = $orders->filter(function ($order) {
                        $totalCustomerQty = $order->packinglist->customer_qty;
                        $currentStock = $order->packinglist->stock;
                        return $totalCustomerQty <= $currentStock; // Completed if stock is sufficient
                    });

                    // Sort pending orders by target date
                    $pendingOrders = $pendingOrders->sortBy(function ($order) {
                        return $order->order->target_date;
                    });

                    // Sort completed orders by target date
                    $completedOrders = $completedOrders->sortBy(function ($order) {
                        return $order->order->target_date;
                    });

                    // Merge pending and completed orders
                    $sortedOrders = $pendingOrders->merge($completedOrders);

                    return [
                        'customer' => $firstOrder->packinglist->customer,
                        'orders' => $sortedOrders,
                    ];
                });
        }

        return view('production.index', compact(
            'products', 
            'employees', 
            'customerOrders',
            'todayCount',
            'yesterdayCount',
            'cancelCount',
            'repackCount',
            'yesterdayRepackCount'
        ));
    }

    public function createBale(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'packinglist_id' => 'required|exists:packinglists,id',
                'qc' => 'required|exists:employees,id',
                'finalist' => 'required|exists:employees,id',
            ]);

            $packinglist = Packinglist::with(['customer', 'product'])->find($request->packinglist_id);
            $qc = Employee::find($request->qc);
            $finalist = Employee::find($request->finalist);

            // Generate bale number
            $today = Carbon::now();
            $datePrefix = $today->format('dmy');
            
            $lastBale = Bale::where('bale_no', 'like', $datePrefix . '%')
                           ->orderBy('bale_no', 'desc')
                           ->first();

            $sequence = '0001';
            if ($lastBale) {
                $lastSequence = (int)substr($lastBale->bale_no, -4);
                $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
            }

            // Create bale
            $bale = Bale::create([
                'bale_no' => $datePrefix . $sequence,
                'packinglist_id' => $request->packinglist_id,
                'qc' => $request->qc,
                'finalist' => $request->finalist,
                'type' => 'production'
            ]);

            // Define QR code URL
            $qrUrl = $url = "https://www.texpolygroup.com/orderData.php?" . http_build_query([
                'o' => $packinglist->customer->short_code,
                'i' => $packinglist->product->name,
                'c' => $packinglist->customer->name,
                'q' => $qc->name,
                'f' => $finalist->name,
                'd' => $bale->created_at->format('Y-m-d H:i:s'),
                'b' => $bale->bale_no
            ]);;

            // Return bale data
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bale created successfully',
                'bale' => $bale,
                'packinglist' => $packinglist,
                'qc' => $qc->name,
                'finalist' => $finalist->name,
                'qrUrl' => $qrUrl
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
