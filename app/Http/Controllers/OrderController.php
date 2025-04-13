<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Packinglist;
use Illuminate\Http\Request;

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

    public function create()
    {
        $customers = Customer::all();
        return view('orders.form', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'status' => 'required|in:production,draft,delivered',
            'container_no' => 'nullable|string',
            'sgs_seal_no' => 'nullable|string',
            'line_seal_no' => 'nullable|string',
            'target_date' => 'required|date',
        ]);

        $customer = Customer::find($request->customer_id);
        $lastOrder = Order::where('order_no', 'like', $customer->short_code . '%')
                         ->orderBy('order_no', 'desc')
                         ->first();

        $orderNumber = 1;
        if ($lastOrder) {
            $orderNumber = (int)substr($lastOrder->order_no, -3) + 1;
        }

        $validated['order_no'] = $customer->short_code . str_pad($orderNumber, 3, '0', STR_PAD_LEFT);
        
        $order = Order::create($validated);
        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully');
    }

    public function show(Order $order)
    {
        $orderlists = $order->orderlists()
            ->with(['packinglist.product'])
            ->join('packinglists', 'orderlists.packinglist_id', '=', 'packinglists.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->where('packinglists.customer_qty', '>', 0)
            ->orderBy('products.short_code', 'asc')
            ->select('orderlists.*')
            ->get();
            
        return view('orders.show', compact('order', 'orderlists'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_date' => 'required|date',
            'status' => 'required|in:production,draft,delivered',
            'container_no' => 'nullable|string',
            'sgs_seal_no' => 'nullable|string',
            'line_seal_no' => 'nullable|string',
            'target_date' => 'required|date',
        ]);

        $order->update($validated);
        return redirect()->back()->with('success', 'Order updated successfully');
    }
}
