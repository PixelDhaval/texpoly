<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\CancelBale;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class CancelController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        
        $query = CancelBale::with(['packinglist.product.category', 'packinglist.product.subcategory', 
                            'packinglist.customer', 'qcEmployee', 'finalistEmployee'])
                    ->whereDate('created_at', $date);

        if ($request->filled('bale_no')) {
            $query->where('bale_no', 'like', '%' . $request->bale_no . '%');
        }

        if ($request->filled('product')) {
            $query->whereHas('packinglist.product', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product . '%')
                  ->orWhere('short_code', 'like', '%' . $request->product . '%');
            });
        }

        if ($request->filled('customer')) {
            $query->whereHas('packinglist', function($q) use ($request) {
                $q->where('customer_id', $request->customer);
            });
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

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $bales = $query->orderBy('created_at', 'desc')->get();
        $customers = Customer::all();
        $categories = Category::all();
        $subcategories = Subcategory::all();

        return view('cancellations.index', compact('bales', 'customers', 'categories', 'subcategories', 'date'));
    }
}
