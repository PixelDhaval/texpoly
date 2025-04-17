<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Packinglist;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RepackingController extends Controller
{
    public function index()
    {
        $customers = Customer::all();
        $employees = Employee::all();
        
        // Get today's repacking bales
        $todayBales = Bale::with(['packinglist.product', 'packinglist.customer', 'qcEmployee', 'finalistEmployee'])
            ->where('type', 'repacking')
            ->whereDate('created_at', now())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('repacking.index', compact('customers', 'employees', 'todayBales'));
    }

    public function getBaleDetails(Request $request)
    {
        $bale = Bale::with(['packinglist.customer', 'packinglist.product'])
            ->where('bale_no', $request->bale_no)
            ->first();

        if (!$bale) {
            return response()->json(['error' => 'Bale not found'], 404);
        }

        return response()->json($bale);
    }

    public function getPackinglists(Request $request)
    {
        if($request->type == "source") {
            $packinglists = Packinglist::with(['product', 'customer'])
            ->where('customer_id', $request->customer_id)
            ->where('stock', '>', 0)
            ->get();
        } else {
            $packinglists = Packinglist::with(['product', 'customer'])
            ->where('customer_id', $request->customer_id)
            ->where('customer_qty', '>', 0)
            ->get();
        }

        $user = Auth::user()->name;

        return response()->json([
            "packing_lists" => $packinglists,
            "user" => $user
        ]);
    }

    public function createBale(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'packinglist_id' => 'required|exists:packinglists,id',
                'ref_packinglist_id' => 'required|exists:packinglists,id',
                'ref_bale_id' => 'nullable|exists:bales,id',
                'qc' => 'required|exists:employees,id',
                'finalist' => 'required|exists:employees,id',
            ]);

            $qc = Employee::find($request->qc);
            $finalist = Employee::find($request->finalist);
            $today = Carbon::now();
            $datePrefix = $today->format('dmy');
            
            $lastBale = Bale::where('bale_no', 'like', $datePrefix . '%')
                           ->orderBy('bale_no', 'desc')
                           ->first();

            $sequence = '0001';
            if ($lastBale) {
                $lastSequence = (int)substr($lastBale->bale_no, -4);
                $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
            }

            $bale = Bale::create([
                'bale_no' => $datePrefix . $sequence,
                'packinglist_id' => $request->packinglist_id,
                'ref_packinglist_id' => $request->ref_packinglist_id,
                'ref_bale_id' => $request->ref_bale_id,
                'qc' => $request->qc,
                'finalist' => $request->finalist,
                'type' => 'repacking'
            ]);

            // Generate QR code URL
            $qrUrl = "https://www.texpolygroup.com/orderData.php?" . http_build_query([
                'o' => $bale->packinglist->customer->short_code,
                'i' => $bale->packinglist->product->name,
                'c' => $bale->packinglist->customer->name,
                'b' => $bale->bale_no,
                'q' => $qc->name,
                'f' => $finalist->name,
                'd' => $bale->created_at->format('Y-m-d H:i:s'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bale created successfully',
                'bale' => $bale,
                'qrUrl' => $qrUrl, // Include the QR URL in the response
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function printBale($id)
    {
        $bale = Bale::with('packinglist.customer', 'packinglist.product')->findOrFail($id);

        $qrUrl = "https://www.texpolygroup.com/orderData.php?" . http_build_query([
            'o' => $bale->packinglist->customer->short_code,
            'i' => $bale->packinglist->product->short_code,
            'c' => $bale->packinglist->customer->name,
            'b' => $bale->bale_no,
        ]);

        return response()->json([
            'success' => true,
            'bale' => $bale,
            'qrUrl' => $qrUrl,
        ]);
    }
}
