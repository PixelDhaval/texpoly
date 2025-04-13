<?php

namespace App\Http\Controllers;

use App\Models\Orderlist;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderlistController extends Controller
{
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'orderlist' => 'required|array',
            'orderlist.*.id' => 'required|exists:orderlists,id',
            'orderlist.*.dispatch_qty' => 'required|integer|min:0'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->orderlist as $item) {
                $orderlist = Orderlist::find($item['id']);
                if ($item['dispatch_qty'] <= ($orderlist->packinglist->stock + $orderlist->dispatch_qty)) {
                    $orderlist->update([
                        'dispatch_qty' => $item['dispatch_qty']
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Order quantities updated successfully');
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Failed to update order quantities: ' . $e->getMessage());
        }
    }
}
