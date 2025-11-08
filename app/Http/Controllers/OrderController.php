<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Packinglist;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrderExport;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->orderBy('target_date');

        if ($request->filled('search')) {
            $query->where('order_no', 'like', '%' . $request->search . '%')
                  ->orWhereHas('customer', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
        }

        $orders = $query->paginate(10);
        return view('orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        if($request->query('customer_id')) {
            $customer = Customer::find($request->query('customer_id'));
            return view('orders.form', compact('customer'));
        }
        $customers = Customer::all();
        return view('orders.form', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'nullable|date',
            'status' => 'required|in:production,draft,delivered',
            'container_no' => 'nullable|string',
            'sgs_seal_no' => 'nullable|string',
            'line_seal_no' => 'nullable|string',
            'target_date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $customer = Customer::find($request->customer_id);
        
        // Get current month and year
        $monthYear = now()->format('my'); // Returns format like '0425' for April 2025
        
        // Get the last order for this customer in current month
        $lastOrder = Order::where('order_no', 'like', $customer->short_code . $monthYear . '%')
                         ->orderBy('order_no', 'desc')
                         ->first();
        // Set order number starting from 1 for each month
        $orderNumber = 1;
        if ($lastOrder) {
            $orderNumber = (int)substr($lastOrder->order_no, -3) + 1;
        }

        // Create order number in format QB0425001
        $validated['order_no'] = $customer->short_code . 
                                $monthYear . 
                                str_pad($orderNumber, 3, '0', STR_PAD_LEFT);
        
        $order = Order::create($validated);
        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully');
    }

    public function show(Order $order)
    {
        $orderlists = $order->orderlists()
            ->with(['packinglist.product'])
            ->join('packinglists', 'orderlists.packinglist_id', '=', 'packinglists.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->where(function($query) use ($order) {
                $query->where('packinglists.customer_qty', '>', 0)
                      ->orWhere('orderlists.dispatch_qty', '>', 0)
                      ->orWhere('packinglists.stock', '>', 0);
            })
            ->orderBy('products.short_code', 'asc')
            ->select('orderlists.*')
            ->get();
            
        return view('orders.show', compact('order', 'orderlists'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_date' => 'nullable|date',
            'status' => 'required|in:production,draft,delivered',
            'container_no' => 'nullable|string',
            'sgs_seal_no' => 'nullable|string',
            'line_seal_no' => 'nullable|string',
            'target_date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $order->update($validated);
        return redirect()->back()->with('success', 'Order updated successfully');
    }

    public function exportExcel(Order $order)
    {
        $filename = 'order_' . $order->order_no . '_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new OrderExport($order), $filename);
    }
}
