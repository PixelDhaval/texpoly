<?php

namespace App\Http\Controllers;

use App\Models\ReprintLabel;
use App\Http\Requests\StoreReprintLabelRequest;
use App\Http\Requests\UpdateReprintLabelRequest;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class ReprintLableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        $query = ReprintLabel::with([
            'packinglist.product.category',
            'packinglist.product.subcategory',
            'packinglist.customer',
            'qcEmployee',
            'finalistEmployee'
        ])
            ->whereDate('created_at', $date);

        if ($request->filled('bale_no')) {
            $query->where('bale_no', 'like', '%' . $request->bale_no . '%');
        }

        if ($request->filled('product')) {
            $query->whereHas('packinglist.product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->product . '%')
                    ->orWhere('short_code', 'like', '%' . $request->product . '%');
            });
        }

        if ($request->filled('customer')) {
            $query->whereHas('packinglist', function ($q) use ($request) {
                $q->where('customer_id', $request->customer);
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('packinglist.product.category', function ($q) use ($request) {
                $q->where('id', $request->category);
            });
        }

        if ($request->filled('subcategory')) {
            $query->whereHas('packinglist.product.subcategory', function ($q) use ($request) {
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

        return view('reprint.index', compact('bales', 'customers', 'categories', 'subcategories', 'date'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('reprint.create');
    }


    /**
     * Display the specified resource.
     */
    public function show(ReprintLabel $reprintLabel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReprintLabel $reprintLabel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReprintLabelRequest $request, ReprintLabel $reprintLabel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReprintLabel $reprintLabel)
    {
        //
    }
}
