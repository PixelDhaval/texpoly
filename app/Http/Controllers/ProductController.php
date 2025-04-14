<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Packinglist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('short_code', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('subcategory')) {
            $query->where('subcategory_id', $request->subcategory);
        }

        $products = Product::with(['category', 'subcategory'])
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                     ->orWhere('short_code', 'like', "%{$search}%");
            })
            ->when($request->category, function($query, $category) {
                $query->where('category_id', $category);
            })
            ->when($request->subcategory, function($query, $subcategory) {
                $query->where('subcategory_id', $subcategory);
            })
            ->orderBy('name')
            ->paginate(10);

        // Add this line to get all products for the modal
        $allProducts = Product::select('id', 'name', 'short_code')->get();

        $categories = Category::all();
        $subcategories = Subcategory::all();

        return view('products.index', compact('products', 'categories', 'subcategories', 'allProducts'));
    }

    public function create()
    {
        $categories = Category::all();
        $subcategories = Subcategory::all();
        return view('products.form', compact('categories', 'subcategories'));
    }

    public function store(StoreProductRequest $request)
    {
        Product::create($request->validated());
        return redirect()->route('products.index')->with('success', 'Product created successfully');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $subcategories = Subcategory::all();
        return view('products.form', compact('product', 'categories', 'subcategories'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return redirect()->route('products.index')->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    public function checkShortCode(Request $request)
    {
        $query = Product::where('short_code', $request->code);
        
        if ($request->filled('id')) {
            $query->where('id', '!=', $request->id);
        }
        
        return response()->json([
            'available' => !$query->exists()
        ]);
    }

    public function merge(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $sourceProduct = Product::findOrFail($id);
            $targetProduct = Product::findOrFail($request->target_product_id);

            // Get all packinglists for the source product
            $packinglists = Packinglist::where('product_id', $sourceProduct->id)->get();

            foreach ($packinglists as $packinglist) {
                // Check if target product packinglist exists for this customer
                $targetPackinglist = Packinglist::where('product_id', $targetProduct->id)
                    ->where('customer_id', $packinglist->customer_id)
                    ->first();

                if ($targetPackinglist) {
                    // Update existing packinglist
                    $targetPackinglist->stock += $packinglist->stock;
                    $targetPackinglist->save();
                    
                    // Delete source packinglist
                    $packinglist->delete();
                } else {
                    // Transfer packinglist to new product
                    $packinglist->product_id = $targetProduct->id;
                    $packinglist->save();
                }
            }

            // Delete the source product
            $sourceProduct->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product merged successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error merging products: ' . $e->getMessage()
            ], 500);
        }
    }
}
