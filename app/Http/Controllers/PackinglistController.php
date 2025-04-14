<?php

namespace App\Http\Controllers;

use App\Models\Packinglist;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackinglistController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('packinglists')->get();
        return view('packinglists.index', compact('customers'));
    }

    public function show(Request $request, Customer $customer)
    {
        $query = Packinglist::join('products', 'packinglists.product_id', '=', 'products.id')
            ->orderBy('products.short_code', 'asc')
            ->select('packinglists.*')
            ->where('customer_id', $customer->id);

        if ($request->filled('search')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('short_code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('label_name')) {
            $query->where('label_name', 'like', '%' . $request->label_name . '%');
        }

        $packinglists = $query->get();
        // return response()->json([
        //     'success' => true,
        //     'data' => $packinglists
        // ]);
        return view('packinglists.show', compact('customer', 'packinglists'));
    }

    public function update(Request $request, Packinglist $packinglist)
    {
        $validated = $request->validate([
            'label_name' => 'nullable|string',
            'customer_qty' => 'nullable|integer',
            'unit' => 'nullable|string',
            'price' => 'nullable|numeric',
            'quantity' => 'nullable|integer',
            'weight' => 'nullable|integer',
            'stock' => 'nullable|integer',
            'is_bold' => 'boolean',
        ]);

        $packinglist->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Updated successfully',
            'data' => $packinglist
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'packinglists' => 'required|array',
            'packinglists.*.id' => 'required|exists:packinglists,id',
            'packinglists.*.label_name' => 'nullable|string',
            'packinglists.*.customer_qty' => 'nullable|integer',
            'packinglists.*.quantity' => 'nullable|integer',
            'packinglists.*.unit' => 'nullable|string',
            'packinglists.*.weight' => 'nullable|integer',
            'packinglists.*.price' => 'nullable|numeric',
            'packinglists.*.stock' => 'nullable|integer',
            'packinglists.*.is_bold' => 'nullable',
        ]);

        try {
            DB::beginTransaction();
            
            // Process updates in chunks of 50 records
            $chunks = array_chunk($request->packinglists, 50, true);
            $totalUpdated = 0;
            $errors = [];

            foreach ($chunks as $chunk) {
                try {
                    foreach ($chunk as $item) {
                        $packinglist = Packinglist::find($item['id']);
                        if ($packinglist) {
                            // Convert is_bold to boolean
                            if (isset($item['is_bold'])) {
                                $item['is_bold'] = filter_var($item['is_bold'], FILTER_VALIDATE_BOOLEAN);
                            }
                            
                            // Remove id from update data
                            unset($item['id']);
                            
                            // Update and track success
                            if ($packinglist->update($item)) {
                                $totalUpdated++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Log the error but continue processing other chunks
                    $errors[] = "Error processing chunk: " . $e->getMessage();
                    Log::error("Packinglist bulk update error: " . $e->getMessage());
                    continue;
                }
            }

            DB::commit();

            // Determine the response based on results
            if ($totalUpdated === count($request->packinglists)) {
                return redirect()->back()->with('success', 'All ' . $totalUpdated . ' items updated successfully');
            } elseif ($totalUpdated > 0) {
                return redirect()->back()
                    ->with('warning', $totalUpdated . ' items updated successfully. Some items failed to update.')
                    ->with('errors', $errors);
            } else {
                throw new \Exception("No items were updated successfully");
            }

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error updating records: ' . $e->getMessage())
                ->with('errors', $errors);
        }
    }
}
