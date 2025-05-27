<?php

namespace App\Http\Controllers;

use App\Models\Bale;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Today's production count
        $todayProduction = Bale::whereDate('created_at', Carbon::today())
            ->where('type', 'production')
            ->count();

        // Active orders count

        // Today's repacking count
        $todayRepacking = Bale::whereDate('created_at', Carbon::today())
            ->where('type', 'repacking')
            ->count();

        // Recent activities
        $recentActivities = Bale::latest()
            ->take(10)
            ->get();

        // Production chart data (last 7 days)
        $productionData = Bale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->where('type', 'production')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartLabels = $productionData->pluck('date')->map(function($date) {
            return Carbon::parse($date)->format('M d');
        });
        $chartData = $productionData->pluck('count');

        // Latest delivered orders
        $deliveredOrders = Order::query()
            ->where('status', 'delivered')
            ->latest('order_date')
            ->take(5)
            ->get();

        // Upcoming orders (target date in future)
        $upcomingOrders = Order::query()
            ->where('status', 'production')// Only future dates
            ->orderBy('target_date', 'asc')                // Sort by target_date ascending
            ->with('customer')                             // Eager load customer relation
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'todayProduction',
            'todayRepacking',
            'recentActivities',
            'chartLabels',
            'chartData',
            'deliveredOrders',
            'upcomingOrders'
        ));
    }
}