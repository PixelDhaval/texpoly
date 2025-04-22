<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Packinglist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaleController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        
        $query = Bale::with(['packinglist.product.category', 'packinglist.product.subcategory', 
                            'packinglist.customer', 'qcEmployee', 'finalistEmployee'])
                    ->whereDate('created_at', $date);

        // Add bale number filter
        if ($request->filled('bale_no')) {
            $query->where('bale_no', 'like', '%' . $request->bale_no . '%');
        }

        if ($request->filled('customer')) {
            $query->whereHas('packinglist', function($q) use ($request) {
                $q->where('customer_id', $request->customer);
            });
        }

        // Add product filter
        if ($request->filled('product')) {
            $query->whereHas('packinglist.product', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product . '%')
                  ->orWhere('short_code', 'like', '%' . $request->product . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->whereHas('packinglist.product.category', function($q) use ($request) {
                $q->where('id', $request->category);
            });
        }

        if ($request->filled('subcategory')) {
            $query->whereHas('packinglist.product.subcategory', function($q) use ($request) {
                $q->where('id', $request->subcategory);
            });
        }

        $bales = $query->get();
        $customers = Customer::all();
        $categories = Category::all();
        $subcategories = Subcategory::all();

        return view('bales.index', compact('bales', 'customers', 'categories', 'subcategories', 'date'));
    }

    public function destroy(Bale $bale)
    {
        $bale->delete();
        return redirect()->back()->with('success', 'Bale deleted successfully');
    }

    public function transferForm()
    {
        $customers = Customer::orderBy('name')->get();
        return view('bales.transfer', compact('customers'));
    }

    public function getPackinglists(Request $request)
    {
        $packinglists = Packinglist::where('customer_id', $request->customer_id)
            ->where('stock', '>', 0)
            ->with(['product'])
            ->get()
            ->map(function ($packinglist) {
                return [
                    'id' => $packinglist->id,
                    'text' => $packinglist->product->name . ' (' . $packinglist->label_name . ') - Stock: ' . $packinglist->stock,
                    'stock' => $packinglist->stock
                ];
            });

        return response()->json($packinglists);
    }

    private function generateBaleNumber($type)
    {
        $today = now()->format('Ymd');
        $lastBale = Bale::where('bale_no', 'like', "{$type}{$today}%")
            ->orderBy('bale_no', 'desc')
            ->first();

        if ($lastBale) {
            $lastNumber = (int) substr($lastBale->bale_no, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return strtoupper($type) . $today . $newNumber;
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_packinglist' => 'required|exists:packinglists,id',
            'to_packinglist' => 'required|exists:packinglists,id|different:from_packinglist',
            'quantity' => 'required|integer|min:1'
        ]);

        $fromPackinglist = Packinglist::findOrFail($request->from_packinglist);
        $toPackinglist = Packinglist::findOrFail($request->to_packinglist);

        if ($fromPackinglist->stock < $request->quantity) {
            return back()
                ->withInput([
                    'fromCustomer' => $fromPackinglist->customer_id,
                    'toCustomer' => $toPackinglist->customer_id,
                    'from_packinglist' => $fromPackinglist->id,
                    'to_packinglist' => $toPackinglist->id,
                    'quantity' => $request->quantity
                ])
                ->withErrors(['quantity' => 'Insufficient stock']);
        }

        try {
            DB::beginTransaction();

            // Create bales for the transfer
            for ($i = 0; $i < $request->quantity; $i++) {
                Bale::create([
                    'type' => 'transfer',
                    'packinglist_id' => $toPackinglist->id,
                    'ref_packinglist_id' => $fromPackinglist->id,
                    'bale_no' => $this->generateBaleNumber('TFR'),
                ]);
            }

            // Update stock
            $fromPackinglist->decrement('stock', $request->quantity);
            $toPackinglist->increment('stock', $request->quantity);

            DB::commit();
            return redirect()->route('bales.transfer')->with('success', 'Bales transferred successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to transfer bales: '. $e->getMessage()]);
        }
    }
}
