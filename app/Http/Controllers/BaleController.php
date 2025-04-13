<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;

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
}
