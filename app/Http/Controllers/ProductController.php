<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Bale;
use App\Models\Customer;
use App\Models\Packinglist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function history(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $products = collect();
        
        if ($request->has('from_date') && $request->has('to_date')) {
            $query = Product::select('products.*')
                ->when($request->filled('customer_id'), function($q) use ($request) {
                    $q->join('packinglists', 'products.id', '=', 'packinglists.product_id')
                      ->where('packinglists.customer_id', $request->customer_id)
                      ->distinct();
                });

            // Add search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('products.name', 'like', "%{$search}%")
                      ->orWhere('products.short_code', 'like', "%{$search}%");
                });
            }

            // Add sorting
            $sortField = $request->input('sort', 'name');
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy("products.{$sortField}", $sortDirection);

            // Get paginated results
            $perPage = $request->input('per_page', 10);
            $productsQuery = $query->paginate($perPage);

            // Map the results
            $products = $productsQuery->through(function($product) use ($request) {
                $fromDate = Carbon::parse($request->from_date)->startOfDay();
                $toDate = Carbon::parse($request->to_date)->endOfDay();
                
                // Calculate movements for all customers or specific customer
                $customerId = $request->filled('customer_id') ? $request->customer_id : null;
                
                // Get current stock
                $currentStock = Packinglist::where('product_id', $product->id)
                    ->when($customerId, function($q) use ($customerId) {
                        $q->where('customer_id', $customerId);
                    })
                    ->sum('stock');

                // Calculate movements between fromDate and now
                $productionAfterFrom = $this->getProductionCount($product->id, $customerId, $fromDate, now());
                $repackingInAfterFrom = $this->getRepackingInCount($product->id, $customerId, $fromDate, now());
                $repackingOutAfterFrom = $this->getRepackingOutCount($product->id, $customerId, $fromDate, now());
                $inwardAfterFrom = $this->getInwardCount($product->id, $customerId, $fromDate, now());
                $outwardAfterFrom = $this->getOutwardCount($product->id, $customerId, $fromDate, now());
                $cuttingAfterFrom = $this->getCuttingCount($product->id, $customerId, $fromDate, now());

                // Calculate opening balance
                $openingBalance = $currentStock - (
                    $productionAfterFrom + 
                    $repackingInAfterFrom - 
                    $repackingOutAfterFrom + 
                    $inwardAfterFrom - 
                    $outwardAfterFrom - 
                    $cuttingAfterFrom
                );

                // Set the calculated values
                $product->opening_balance = $openingBalance;
                $product->production_count = $this->getProductionCount($product->id, $customerId, $fromDate, $toDate);
                $product->repacking_in = $this->getRepackingInCount($product->id, $customerId, $fromDate, $toDate);
                $product->repacking_out = $this->getRepackingOutCount($product->id, $customerId, $fromDate, $toDate);
                $product->inward = $this->getInwardCount($product->id, $customerId, $fromDate, $toDate);
                $product->outward = $this->getOutwardCount($product->id, $customerId, $fromDate, $toDate);
                $product->cutting = $this->getCuttingCount($product->id, $customerId, $fromDate, $toDate);
                
                // Calculate closing balance
                $product->closing_balance = $openingBalance + 
                    $product->production_count + 
                    $product->repacking_in - 
                    $product->repacking_out + 
                    $product->inward - 
                    $product->outward - 
                    $product->cutting;

                return $product;
            });
        }

        return view('products.history', compact('customers', 'products'));
    }

    public function historyDetail(Request $request)
    {
        $product = Product::findOrFail($request->product_id);
        $fromDate = Carbon::parse($request->from_date)->startOfDay();
        $toDate = Carbon::parse($request->to_date)->endOfDay();
        $customerId = $request->filled('customer_id') ? $request->customer_id : null;
        $customers = Customer::orderBy('name')->get();

        // Get current stock
        $currentStock = Packinglist::where('product_id', $product->id)
            ->when($customerId, function($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            })
            ->sum('stock');

        // Calculate movements between fromDate and now
        $productionAfterFrom = $this->getProductionCount($product->id, $customerId, $fromDate, now());
        $repackingInAfterFrom = $this->getRepackingInCount($product->id, $customerId, $fromDate, now());
        $repackingOutAfterFrom = $this->getRepackingOutCount($product->id, $customerId, $fromDate, now());
        $inwardAfterFrom = $this->getInwardCount($product->id, $customerId, $fromDate, now());
        $outwardAfterFrom = $this->getOutwardCount($product->id, $customerId, $fromDate, now());
        $cuttingAfterFrom = $this->getCuttingCount($product->id, $customerId, $fromDate, now());

        // Calculate opening balance
        $openingBalance = $currentStock - (
            $productionAfterFrom + 
            $repackingInAfterFrom - 
            $repackingOutAfterFrom + 
            $inwardAfterFrom - 
            $outwardAfterFrom - 
            $cuttingAfterFrom
        );

        // Get bales for selected period with customer relationships
        $productionBales = Bale::with(['packinglist.customer', 'qcEmployee', 'finalistEmployee'])
            ->where('type', 'production')
            ->whereHas('packinglist', function($query) use ($product, $customerId) {
                $query->where('product_id', $product->id)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $repackingBales = Bale::with(['packinglist.customer', 'packinglist.product', 
                                     'refPackinglist.customer', 'refPackinglist.product',
                                     'qcEmployee', 'finalistEmployee'])
            ->where('type', 'repacking')
            ->where(function($query) use ($product, $customerId) {
                $query->whereHas('packinglist', function($q) use ($product, $customerId) {
                    $q->where('product_id', $product->id)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
                })->orWhereHas('refPackinglist', function($q) use ($product, $customerId) {
                    $q->where('product_id', $product->id)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
                });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $inwardBales = Bale::with(['packinglist.customer', 'packinglist.product', 'plant'])
            ->where('type', 'inward')
            ->whereHas('packinglist', function($query) use ($product, $customerId) {
                $query->where('product_id', $product->id)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $outwardBales = Bale::with(['packinglist.customer', 'packinglist.product', 'plant'])
            ->where('type', 'outward')
            ->whereHas('packinglist', function($query) use ($product, $customerId) {
                $query->where('product_id', $product->id)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $cuttingBales = Bale::with(['packinglist.customer', 'packinglist.product'])
            ->where('type', 'cutting')
            ->whereHas('packinglist', function($query) use ($product, $customerId) {
                $query->where('product_id', $product->id)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        // Calculate movements for selected period
        $movements = [
            'production' => $this->getProductionCount($product->id, $customerId, $fromDate, $toDate),
            'repacking_in' => $this->getRepackingInCount($product->id, $customerId, $fromDate, $toDate),
            'repacking_out' => $this->getRepackingOutCount($product->id, $customerId, $fromDate, $toDate),
            'inward' => $this->getInwardCount($product->id, $customerId, $fromDate, $toDate),
            'outward' => $this->getOutwardCount($product->id, $customerId, $fromDate, $toDate),
            'cutting' => $this->getCuttingCount($product->id, $customerId, $fromDate, $toDate)
        ];

        // Calculate closing balance
        $closingBalance = $openingBalance + 
            $movements['production'] + 
            $movements['repacking_in'] - 
            $movements['repacking_out'] + 
            $movements['inward'] - 
            $movements['outward'] - 
            $movements['cutting'];

        return view('products.history-detail', compact(
            'product',
            'productionBales',
            'repackingBales',
            'inwardBales',
            'outwardBales',
            'cuttingBales',
            'openingBalance',
            'closingBalance',
            'currentStock',
            'movements',
            'fromDate',
            'toDate',
            'customers'  // Add customers to the view
        ));
    }

    private function getProductionCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return Bale::where('type', 'production')
            ->whereHas('packinglist', function($query) use ($productId, $customerId) {
                $query->where('product_id', $productId)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }

    private function getRepackingInCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return Bale::where('type', 'repacking')
            ->whereHas('packinglist', function($query) use ($productId, $customerId) {
                $query->where('product_id', $productId)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }

    private function getRepackingOutCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return Bale::where('type', 'repacking')
            ->whereHas('refPackinglist', function($query) use ($productId, $customerId) {
                $query->where('product_id', $productId)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }

    private function getInwardCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return Bale::where('type', 'inward')
            ->whereHas('packinglist', function($query) use ($productId, $customerId) {
                $query->where('product_id', $productId)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }

    private function getOutwardCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return Bale::where('type', 'outward')
            ->whereHas('packinglist', function($query) use ($productId, $customerId) {
                $query->where('product_id', $productId)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }

    private function getCuttingCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return Bale::where('type', 'cutting')
            ->whereHas('packinglist', function($query) use ($productId, $customerId) {
                $query->where('product_id', $productId)
                      ->when($customerId, function($q) use ($customerId) {
                          $q->where('customer_id', $customerId);
                      });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }
}
