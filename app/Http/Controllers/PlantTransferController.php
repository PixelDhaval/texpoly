<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\Customer;
use App\Models\Plant;
use App\Models\Packinglist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlantTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = Bale::with(['packinglist.customer', 'packinglist.product', 'plant'])
            ->whereIn('type', ['inward', 'outward', 'cutting'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('customer')) {
            $query->whereHas('packinglist', function($q) use ($request) {
                $q->where('customer_id', $request->customer);
            });
        }

        if ($request->filled('search')) {
            $query->whereHas('packinglist', function($q) use ($request) {
                $q->where('label_name', 'like', '%' . $request->search . '%')
                  ->orWhereHas('product', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('plant')) {
            $query->where('plant_id', $request->plant);
        }

        $bales = $query->paginate(15);
        $customers = Customer::all();
        $plants = Plant::all();

        return view('plant-transfers.index', compact('bales', 'customers', 'plants'));
    }

    public function getPackinglists(Request $request)
    {
        $packinglists = Packinglist::with(['product'])
            ->where('customer_id', $request->customer_id)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'text' => "{$item->product->name} - {$item->label_name}"
                ];
            });

        return response()->json($packinglists);
    }

    public function store(Request $request)
    {
        $request->validate([
            'packinglist_id' => 'required|exists:packinglists,id',
            'type' => 'required|in:inward,outward,cutting',
            'plant_id' => 'required|exists:plants,id',
            'quantity' => 'required|integer|min:1',
            'created_at' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $date = Carbon::parse($request->created_at);
            $datePrefix = $date->format('dmy');

            for ($i = 0; $i < $request->quantity; $i++) {
                $lastBale = Bale::where('bale_no', 'like', $datePrefix . '%')
                               ->orderBy('bale_no', 'desc')
                               ->first();

                $sequence = '0001';
                if ($lastBale) {
                    $lastSequence = (int)substr($lastBale->bale_no, -4);
                    $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
                }

                Bale::create([
                    'bale_no' => $datePrefix . $sequence,
                    'packinglist_id' => $request->packinglist_id,
                    'type' => $request->type,
                    'plant_id' => $request->plant_id,
                    'created_at' => $request->created_at,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
