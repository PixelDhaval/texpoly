<?php

namespace App\Http\Controllers;

use App\Models\Packinglist;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackinglistController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount([
            'packinglists',
            'packinglists as active_products_count' => function($query) {
                $query->where('customer_qty', '>', 0);
            }
        ])->get();
        
        return view('packinglists.index', compact('customers'));
    }

    public function show(Request $request, Customer $customer)
    {
        $query = Packinglist::join('products', 'packinglists.product_id', '=', 'products.id')
            ->orderBy('products.name', 'asc')
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
            'is_weight' => 'boolean',
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
            'packinglists.*.is_bold' => 'nullable',
            'packinglists.*.is_weight' => 'nullable',
            'packinglists.*.stop_till' => 'nullable|'. 'date_format:Y-m-d\TH:i',
        ]);

        $errors = [];

        try {
            DB::beginTransaction();
            
            $totalUpdated = 0;

            foreach ($request->packinglists as $index => $item) {
                try {
                    $packinglist = Packinglist::find($item['id']);

                    if (!$packinglist) {
                        $errors[] = "Item #{$item['id']} not found";
                        continue;
                    }

                    if (array_key_exists('is_bold', $item)) {
                        $item['is_bold'] = filter_var($item['is_bold'], FILTER_VALIDATE_BOOLEAN);
                    }

                    if (array_key_exists('is_weight', $item)) {
                        $item['is_weight'] = filter_var($item['is_weight'], FILTER_VALIDATE_BOOLEAN);
                    }
                    
                    if (array_key_exists('stop_till', $item)) {
                        if (blank($item['stop_till'])) {
                            $item['stop_till'] = null;
                        } else {
                            $item['stop_till'] = Carbon::createFromFormat('Y-m-d\TH:i', $item['stop_till'])->format('Y-m-d H:i:s');
                        }
                    }

                    $packinglist->fill($item);
                    $isDirty = $packinglist->isDirty();

                    if ($isDirty && $packinglist->save()) {
                        $totalUpdated++;
                    }
                } catch (\Exception $e) {
                    $itemId = $item['id'] ?? ($index + 1);
                    $errors[] = "Error updating item #{$itemId}: " . $e->getMessage();
                    Log::error("Packinglist bulk update error for item #{$itemId}: " . $e->getMessage());
                }
            }

            DB::commit();

            // Determine the response based on results
            if ($totalUpdated === count($request->packinglists) && count($errors) === 0) {
                return redirect()->back()->with('success', 'All ' . $totalUpdated . ' items updated successfully');
            } elseif ($totalUpdated > 0 || count($errors) > 0) {
                $warningMessage = $totalUpdated . ' items updated successfully.';
                if (count($errors) > 0) {
                    $warningMessage .= ' Some items failed to update.';
                }

                return redirect()->back()
                    ->with('warning', $warningMessage)
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
