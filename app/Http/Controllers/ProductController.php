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
use App\Exports\CustomerBalanceExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('perPage', 10); // Default to 10

        $query = Product::with(['category', 'subcategory']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . strtoupper($request->search) . '%')
                  ->orWhere('short_code', 'like', '%' . strtoupper($request->search) . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('subcategory')) {
            $query->where('subcategory_id', $request->subcategory);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $products = $query->orderBy('name')->paginate($perPage);

        // Add this line to get all products for the modal
        $allProducts = Product::select('id', 'name', 'short_code')->orderBy('name')->get();

        $categories = Category::all();
        $subcategories = Subcategory::all();

        // Add available per page options
        $perPageOptions = [10, 25, 50, 100];

        return view('products.index', compact('products', 'categories', 'subcategories', 'allProducts', 'perPageOptions', 'perPage'));
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

                    // Update related records with new packinglist_id
                    DB::table('orderlists')->where('packinglist_id', $packinglist->id)
                        ->update(['packinglist_id' => $targetPackinglist->id]);
                    
                    DB::table('cancel_bales')->where('packinglist_id', $packinglist->id)
                        ->update(['packinglist_id' => $targetPackinglist->id]);
                    
                    DB::table('bales')->where('packinglist_id', $packinglist->id)
                        ->update(['packinglist_id' => $targetPackinglist->id]);

                    DB::table('bales')->where('ref_packinglist_id', $packinglist->id)
                        ->update(['ref_packinglist_id' => $targetPackinglist->id]);

                    // Delete the old packinglist
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
        $categories = Category::all();
        $subcategories = Subcategory::all();
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
                $search = strtoupper($request->search);
                $query->where(function($q) use ($search) {
                    $q->where('products.name', 'like', "%{$search}%")
                      ->orWhere('products.short_code', 'like', "%{$search}%");
                });
            }

            // Add category filter
            if ($request->filled('category')) {
                $query->where('products.category_id', $request->category);
            }

            // Add subcategory (section) filter
            if ($request->filled('subcategory')) {
                $query->where('products.subcategory_id', $request->subcategory);
            }

            // Add type filter
            if ($request->filled('type')) {
                $query->where('products.type', $request->type);
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
                $dispatchAfterFrom = $this->getDispatchCount($product->id, $customerId, $fromDate, now());
                $transferInAfterFrom = $this->getTransferInCount($product->id, $customerId, $fromDate, now());
                $transferOutAfterFrom = $this->getTransferOutCount($product->id, $customerId, $fromDate, now());

                // Calculate opening balance
                $openingBalance = $currentStock - (
                    $productionAfterFrom + 
                    $repackingInAfterFrom - 
                    $repackingOutAfterFrom + 
                    $inwardAfterFrom - 
                    $outwardAfterFrom - 
                    $cuttingAfterFrom -
                    $dispatchAfterFrom +
                    $transferInAfterFrom -
                    $transferOutAfterFrom
                );

                // Set the calculated values
                $product->opening_balance = $openingBalance;
                $product->production_count = $this->getProductionCount($product->id, $customerId, $fromDate, $toDate);
                $product->repacking_in = $this->getRepackingInCount($product->id, $customerId, $fromDate, $toDate);
                $product->repacking_out = $this->getRepackingOutCount($product->id, $customerId, $fromDate, $toDate);
                $product->inward = $this->getInwardCount($product->id, $customerId, $fromDate, $toDate);
                $product->outward = $this->getOutwardCount($product->id, $customerId, $fromDate, $toDate);
                $product->cutting = $this->getCuttingCount($product->id, $customerId, $fromDate, $toDate);
                $product->dispatch = $this->getDispatchCount($product->id, $customerId, $fromDate, $toDate);
                $product->transfer_in = $this->getTransferInCount($product->id, $customerId, $fromDate, $toDate);
                $product->transfer_out = $this->getTransferOutCount($product->id, $customerId, $fromDate, $toDate);
                
                // Calculate closing balance
                $product->closing_balance = $openingBalance + 
                    $product->production_count + 
                    $product->repacking_in - 
                    $product->repacking_out + 
                    $product->inward - 
                    $product->outward - 
                    $product->cutting -
                    $product->dispatch +
                    $product->transfer_in -
                    $product->transfer_out;

                return $product;
            });
        }

        return view('products.history', compact('customers', 'products', 'categories', 'subcategories'));
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
        $dispatchAfterFrom = $this->getDispatchCount($product->id, $customerId, $fromDate, now());
        $transferInAfterFrom = $this->getTransferInCount($product->id, $customerId, $fromDate, now());
        $transferOutAfterFrom = $this->getTransferOutCount($product->id, $customerId, $fromDate, now());

        // Calculate opening balance
        $openingBalance = $currentStock - (
            $productionAfterFrom + 
            $repackingInAfterFrom - 
            $repackingOutAfterFrom + 
            $inwardAfterFrom - 
            $outwardAfterFrom - 
            $cuttingAfterFrom -
            $dispatchAfterFrom +
            $transferInAfterFrom -
            $transferOutAfterFrom
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

        $transferInBales = Bale::with(['packinglist.customer', 'packinglist.product'])
            ->where('type', 'transfer')
            ->whereHas('packinglist', function($query) use ($product, $customerId) {
                $query->where('product_id', $product->id)
                    ->when($customerId, function($q) use ($customerId) {
                        $q->where('customer_id', $customerId);
                    });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        $transferOutBales = Bale::with(['refPackinglist.customer', 'refPackinglist.product'])
            ->where('type', 'transfer')
            ->whereHas('refPackinglist', function($query) use ($product, $customerId) {
                $query->where('product_id', $product->id)
                    ->when($customerId, function($q) use ($customerId) {
                        $q->where('customer_id', $customerId);
                    });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        // Get dispatch orders for the selected period
        $dispatchBales = DB::table('orderlists')
            ->join('packinglists', 'orderlists.packinglist_id', '=', 'packinglists.id')
            ->join('orders', 'orderlists.order_id', '=', 'orders.id')
            ->where('packinglists.product_id', $product->id)
            ->when($customerId, function($q) use ($customerId) {
                $q->where('packinglists.customer_id', $customerId);
            })
            ->whereBetween('orders.order_date', [$fromDate, $toDate])
            ->where('orderlists.dispatch_qty', '>', 0)
            ->select(
                'orders.order_date as created_at',
                'orderlists.dispatch_qty',
                'orderlists.order_id',
                'orderlists.packinglist_id',
                'orders.order_no as bale_no'
            )
            ->get();

        // Calculate movements for selected period
        $movements = [
            'production' => $this->getProductionCount($product->id, $customerId, $fromDate, $toDate),
            'repacking_in' => $this->getRepackingInCount($product->id, $customerId, $fromDate, $toDate),
            'repacking_out' => $this->getRepackingOutCount($product->id, $customerId, $fromDate, $toDate),
            'inward' => $this->getInwardCount($product->id, $customerId, $fromDate, $toDate),
            'outward' => $this->getOutwardCount($product->id, $customerId, $fromDate, $toDate),
            'cutting' => $this->getCuttingCount($product->id, $customerId, $fromDate, $toDate),
            'dispatch' => $this->getDispatchCount($product->id, $customerId, $fromDate, $toDate),
            'transfer_in' => $this->getTransferInCount($product->id, $customerId, $fromDate, $toDate),
            'transfer_out' => $this->getTransferOutCount($product->id, $customerId, $fromDate, $toDate)
        ];

        // Calculate closing balance
        $closingBalance = $openingBalance + 
            $movements['production'] + 
            $movements['repacking_in'] - 
            $movements['repacking_out'] + 
            $movements['inward'] - 
            $movements['outward'] - 
            $movements['cutting'] -
            $movements['dispatch'] +
            $movements['transfer_in'] -
            $movements['transfer_out'];

        return view('products.history-detail', compact(
            'product',
            'productionBales',
            'repackingBales',
            'inwardBales',
            'outwardBales',
            'cuttingBales',
            'transferInBales',
            'transferOutBales',
            'dispatchBales',
            'openingBalance',
            'closingBalance',
            'currentStock',
            'movements',
            'fromDate',
            'toDate',
            'customers'
        ));
    }

    
    public function customerBalance(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $categories = Category::all();
        $subcategories = Subcategory::all();
        $balances = collect();
        
        if ($request->has('from_date') && $request->has('to_date')) {
            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate = Carbon::parse($request->to_date)->endOfDay();

            // Build base query for customers with stock
            $query = Customer::query()
                ->whereHas('packinglists', function($q) use ($request) {
                    if ($request->filled('category')) {
                        $q->whereHas('product', function($pq) use ($request) {
                            $pq->where('category_id', $request->category);
                        });
                    }
                    if ($request->filled('subcategory')) {
                        $q->whereHas('product', function($pq) use ($request) {
                            $pq->where('subcategory_id', $request->subcategory);
                        });
                    }
                    if ($request->filled('type')) {
                        $q->whereHas('product', function($pq) use ($request) {
                            $pq->where('type', $request->type);
                        });
                    }
                });

            // Apply search filter
            if ($request->filled('search')) {
                $search = strtoupper($request->search);
                $query->where('name', 'like', "%{$search}%");
            }

            // Add sorting
            $sortField = $request->input('sort', 'name');
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            // Get paginated results
            $perPage = $request->input('per_page', 10);
            $customersQuery = $query->paginate($perPage);

            // Get customer IDs for bulk calculations
            $customerIds = $customersQuery->pluck('id')->toArray();

            // Build product filter conditions
            $productFilter = function($q) use ($request) {
                if ($request->filled('category')) {
                    $q->where('category_id', $request->category);
                }
                if ($request->filled('subcategory')) {
                    $q->where('subcategory_id', $request->subcategory);
                }
                if ($request->filled('type')) {
                    $q->where('type', $request->type);
                }
            };

            // Bulk calculate current stocks
            $currentStocks = Packinglist::select('customer_id', DB::raw('SUM(stock) as total_stock'))
                ->whereIn('customer_id', $customerIds)
                ->whereHas('product', $productFilter)
                ->groupBy('customer_id')
                ->pluck('total_stock', 'customer_id');

            // Bulk calculate movements after fromDate
            $productionAfter = $this->bulkGetMovements('production', 'packinglist', $customerIds, $productFilter, $fromDate, now());
            $repackingInAfter = $this->bulkGetMovements('repacking', 'packinglist', $customerIds, $productFilter, $fromDate, now());
            $repackingOutAfter = $this->bulkGetMovements('repacking', 'refPackinglist', $customerIds, $productFilter, $fromDate, now());
            $inwardAfter = $this->bulkGetMovements('inward', 'packinglist', $customerIds, $productFilter, $fromDate, now());
            $outwardAfter = $this->bulkGetMovements('outward', 'packinglist', $customerIds, $productFilter, $fromDate, now());
            $cuttingAfter = $this->bulkGetMovements('cutting', 'packinglist', $customerIds, $productFilter, $fromDate, now());
            $transferInAfter = $this->bulkGetMovements('transfer', 'packinglist', $customerIds, $productFilter, $fromDate, now());
            $transferOutAfter = $this->bulkGetMovements('transfer', 'refPackinglist', $customerIds, $productFilter, $fromDate, now());
            $dispatchAfter = $this->bulkGetDispatchMovements($customerIds, $productFilter, $fromDate, now());

            // Bulk calculate movements for selected period
            $production = $this->bulkGetMovements('production', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
            $repackingIn = $this->bulkGetMovements('repacking', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
            $repackingOut = $this->bulkGetMovements('repacking', 'refPackinglist', $customerIds, $productFilter, $fromDate, $toDate);
            $inward = $this->bulkGetMovements('inward', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
            $outward = $this->bulkGetMovements('outward', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
            $cutting = $this->bulkGetMovements('cutting', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
            $transferIn = $this->bulkGetMovements('transfer', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
            $transferOut = $this->bulkGetMovements('transfer', 'refPackinglist', $customerIds, $productFilter, $fromDate, $toDate);
            $dispatch = $this->bulkGetDispatchMovements($customerIds, $productFilter, $fromDate, $toDate);

            // Map the results
            $balances = $customersQuery->through(function($customer) use (
                $currentStocks, $productionAfter, $repackingInAfter, $repackingOutAfter,
                $inwardAfter, $outwardAfter, $cuttingAfter, $transferInAfter, $transferOutAfter, $dispatchAfter,
                $production, $repackingIn, $repackingOut, $inward, $outward, $cutting, $transferIn, $transferOut, $dispatch
            ) {
                $customerId = $customer->id;
                $currentStock = $currentStocks[$customerId] ?? 0;

                // Calculate opening balance
                $openingBalance = $currentStock - (
                    ($productionAfter[$customerId] ?? 0) + 
                    ($repackingInAfter[$customerId] ?? 0) - 
                    ($repackingOutAfter[$customerId] ?? 0) + 
                    ($inwardAfter[$customerId] ?? 0) - 
                    ($outwardAfter[$customerId] ?? 0) - 
                    ($cuttingAfter[$customerId] ?? 0) -
                    ($dispatchAfter[$customerId] ?? 0) +
                    ($transferInAfter[$customerId] ?? 0) -
                    ($transferOutAfter[$customerId] ?? 0)
                );

                // Get movements for the selected period
                $customerProduction = $production[$customerId] ?? 0;
                $customerRepackingIn = $repackingIn[$customerId] ?? 0;
                $customerRepackingOut = $repackingOut[$customerId] ?? 0;
                $customerInward = $inward[$customerId] ?? 0;
                $customerOutward = $outward[$customerId] ?? 0;
                $customerCutting = $cutting[$customerId] ?? 0;
                $customerTransferIn = $transferIn[$customerId] ?? 0;
                $customerTransferOut = $transferOut[$customerId] ?? 0;
                $customerDispatch = $dispatch[$customerId] ?? 0;

                // Calculate closing balance
                $closingBalance = $openingBalance + 
                    $customerProduction + 
                    $customerRepackingIn - 
                    $customerRepackingOut + 
                    $customerInward - 
                    $customerOutward - 
                    $customerCutting -
                    $customerDispatch +
                    $customerTransferIn -
                    $customerTransferOut;

                // Set calculated values on customer object
                $customer->opening_balance = $openingBalance;
                $customer->closing_balance = $closingBalance;
                $customer->production = $customerProduction;
                $customer->repacking_in = $customerRepackingIn;
                $customer->repacking_out = $customerRepackingOut;
                $customer->transfer_in = $customerTransferIn;
                $customer->transfer_out = $customerTransferOut;
                $customer->inward = $customerInward;
                $customer->outward = $customerOutward;
                $customer->cutting = $customerCutting;
                $customer->dispatch = $customerDispatch;

                return $customer;
            });
        }

        return view('products.customer-balance', compact('customers', 'balances', 'categories', 'subcategories'));
    }

    public function customerBalanceExport(Request $request)
    {
        if (!$request->has('from_date') || !$request->has('to_date')) {
            return redirect()->back()->with('error', 'Please select date range first');
        }

        $fromDate = Carbon::parse($request->from_date)->startOfDay();
        $toDate = Carbon::parse($request->to_date)->endOfDay();

        // Build base query for customers with stock (same as customerBalance method)
        $query = Customer::query()
            ->whereHas('packinglists', function($q) use ($request) {
                if ($request->filled('category')) {
                    $q->whereHas('product', function($pq) use ($request) {
                        $pq->where('category_id', $request->category);
                    });
                }
                if ($request->filled('subcategory')) {
                    $q->whereHas('product', function($pq) use ($request) {
                        $pq->where('subcategory_id', $request->subcategory);
                    });
                }
                if ($request->filled('type')) {
                    $q->whereHas('product', function($pq) use ($request) {
                        $pq->where('type', $request->type);
                    });
                }
            });

        // Apply search filter
        if ($request->filled('search')) {
            $search = strtoupper($request->search);
            $query->where('name', 'like', "%{$search}%");
        }

        // Add sorting
        $sortField = $request->input('sort', 'name');
        $sortDirection = $request->input('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Get all results (no pagination for export)
        $customers = $query->get();
        $customerIds = $customers->pluck('id')->toArray();

        // Build product filter conditions
        $productFilter = function($q) use ($request) {
            if ($request->filled('category')) {
                $q->where('category_id', $request->category);
            }
            if ($request->filled('subcategory')) {
                $q->where('subcategory_id', $request->subcategory);
            }
            if ($request->filled('type')) {
                $q->where('type', $request->type);
            }
        };

        // Bulk calculate current stocks
        $currentStocks = Packinglist::select('customer_id', DB::raw('SUM(stock) as total_stock'))
            ->whereIn('customer_id', $customerIds)
            ->whereHas('product', $productFilter)
            ->groupBy('customer_id')
            ->pluck('total_stock', 'customer_id');

        // Bulk calculate movements after fromDate
        $productionAfter = $this->bulkGetMovements('production', 'packinglist', $customerIds, $productFilter, $fromDate, now());
        $repackingInAfter = $this->bulkGetMovements('repacking', 'packinglist', $customerIds, $productFilter, $fromDate, now());
        $repackingOutAfter = $this->bulkGetMovements('repacking', 'refPackinglist', $customerIds, $productFilter, $fromDate, now());
        $inwardAfter = $this->bulkGetMovements('inward', 'packinglist', $customerIds, $productFilter, $fromDate, now());
        $outwardAfter = $this->bulkGetMovements('outward', 'packinglist', $customerIds, $productFilter, $fromDate, now());
        $cuttingAfter = $this->bulkGetMovements('cutting', 'packinglist', $customerIds, $productFilter, $fromDate, now());
        $transferInAfter = $this->bulkGetMovements('transfer', 'packinglist', $customerIds, $productFilter, $fromDate, now());
        $transferOutAfter = $this->bulkGetMovements('transfer', 'refPackinglist', $customerIds, $productFilter, $fromDate, now());
        $dispatchAfter = $this->bulkGetDispatchMovements($customerIds, $productFilter, $fromDate, now());

        // Bulk calculate movements for selected period
        $production = $this->bulkGetMovements('production', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
        $repackingIn = $this->bulkGetMovements('repacking', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
        $repackingOut = $this->bulkGetMovements('repacking', 'refPackinglist', $customerIds, $productFilter, $fromDate, $toDate);
        $inward = $this->bulkGetMovements('inward', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
        $outward = $this->bulkGetMovements('outward', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
        $cutting = $this->bulkGetMovements('cutting', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
        $transferIn = $this->bulkGetMovements('transfer', 'packinglist', $customerIds, $productFilter, $fromDate, $toDate);
        $transferOut = $this->bulkGetMovements('transfer', 'refPackinglist', $customerIds, $productFilter, $fromDate, $toDate);
        $dispatch = $this->bulkGetDispatchMovements($customerIds, $productFilter, $fromDate, $toDate);

        // Map the results
        $balances = $customers->map(function($customer) use (
            $currentStocks, $productionAfter, $repackingInAfter, $repackingOutAfter,
            $inwardAfter, $outwardAfter, $cuttingAfter, $transferInAfter, $transferOutAfter, $dispatchAfter,
            $production, $repackingIn, $repackingOut, $inward, $outward, $cutting, $transferIn, $transferOut, $dispatch
        ) {
            $customerId = $customer->id;
            $currentStock = $currentStocks[$customerId] ?? 0;

            // Calculate opening balance
            $openingBalance = $currentStock - (
                ($productionAfter[$customerId] ?? 0) + 
                ($repackingInAfter[$customerId] ?? 0) - 
                ($repackingOutAfter[$customerId] ?? 0) + 
                ($inwardAfter[$customerId] ?? 0) - 
                ($outwardAfter[$customerId] ?? 0) - 
                ($cuttingAfter[$customerId] ?? 0) -
                ($dispatchAfter[$customerId] ?? 0) +
                ($transferInAfter[$customerId] ?? 0) -
                ($transferOutAfter[$customerId] ?? 0)
            );

            // Get movements for the selected period
            $customerProduction = $production[$customerId] ?? 0;
            $customerRepackingIn = $repackingIn[$customerId] ?? 0;
            $customerRepackingOut = $repackingOut[$customerId] ?? 0;
            $customerInward = $inward[$customerId] ?? 0;
            $customerOutward = $outward[$customerId] ?? 0;
            $customerCutting = $cutting[$customerId] ?? 0;
            $customerTransferIn = $transferIn[$customerId] ?? 0;
            $customerTransferOut = $transferOut[$customerId] ?? 0;
            $customerDispatch = $dispatch[$customerId] ?? 0;

            // Calculate closing balance
            $closingBalance = $openingBalance + 
                $customerProduction + 
                $customerRepackingIn - 
                $customerRepackingOut + 
                $customerInward - 
                $customerOutward - 
                $customerCutting -
                $customerDispatch +
                $customerTransferIn -
                $customerTransferOut;

            // Set calculated values on customer object
            $customer->opening_balance = $openingBalance;
            $customer->closing_balance = $closingBalance;
            $customer->production = $customerProduction;
            $customer->repacking_in = $customerRepackingIn;
            $customer->repacking_out = $customerRepackingOut;
            $customer->transfer_in = $customerTransferIn;
            $customer->transfer_out = $customerTransferOut;
            $customer->inward = $customerInward;
            $customer->outward = $customerOutward;
            $customer->cutting = $customerCutting;
            $customer->dispatch = $customerDispatch;

            return $customer;
        });

        $filename = 'customer-balance-' . $fromDate->format('Y-m-d') . '-to-' . $toDate->format('Y-m-d') . '.xlsx';
        
        return Excel::download(
            new CustomerBalanceExport($balances, $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')), 
            $filename
        );
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

    private function getDispatchCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return DB::table('orderlists')
            ->join('packinglists', 'orderlists.packinglist_id', '=', 'packinglists.id')
            ->join('orders', 'orderlists.order_id', '=', 'orders.id')
            ->where('packinglists.product_id', $productId)
            ->when($customerId, function($q) use ($customerId) {
                $q->where('packinglists.customer_id', $customerId);
            })
            ->whereBetween('orders.order_date', [$fromDate, $toDate])
            ->sum('orderlists.dispatch_qty');
    }

    private function getTransferInCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return Bale::where('type', 'transfer')
            ->whereHas('packinglist', function($query) use ($productId, $customerId) {
                $query->where('product_id', $productId)
                    ->when($customerId, function($q) use ($customerId) {
                        $q->where('customer_id', $customerId);
                    });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }

    private function getTransferOutCount($productId, $customerId = null, $fromDate, $toDate)
    {
        return Bale::where('type', 'transfer')
            ->whereHas('refPackinglist', function($query) use ($productId, $customerId) {
                $query->where('product_id', $productId)
                    ->when($customerId, function($q) use ($customerId) {
                        $q->where('customer_id', $customerId);
                    });
            })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();
    }

    private function bulkGetMovements($type, $relation, $customerIds, $productFilter, $fromDate, $toDate)
    {
        return Bale::select('packinglists.customer_id', DB::raw('COUNT(*) as count'))
            ->join('packinglists', function($join) use ($relation) {
                $join->on('bales.' . ($relation === 'refPackinglist' ? 'ref_packinglist_id' : 'packinglist_id'), '=', 'packinglists.id');
            })
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->where('bales.type', $type)
            ->whereIn('packinglists.customer_id', $customerIds)
            ->where($productFilter)
            ->whereBetween('bales.created_at', [$fromDate, $toDate])
            ->groupBy('packinglists.customer_id')
            ->pluck('count', 'customer_id');
    }

    private function bulkGetDispatchMovements($customerIds, $productFilter, $fromDate, $toDate)
    {
        return DB::table('orderlists')
            ->join('packinglists', 'orderlists.packinglist_id', '=', 'packinglists.id')
            ->join('orders', 'orderlists.order_id', '=', 'orders.id')
            ->join('products', 'packinglists.product_id', '=', 'products.id')
            ->whereIn('packinglists.customer_id', $customerIds)
            ->where($productFilter)
            ->whereBetween('orders.order_date', [$fromDate, $toDate])
            ->groupBy('packinglists.customer_id')
            ->select('packinglists.customer_id', DB::raw('SUM(orderlists.dispatch_qty) as total'))
            ->pluck('total', 'customer_id');
    }
}
