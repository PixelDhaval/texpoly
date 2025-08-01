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
        // Today's production count (all types)
        $todayProduction = Bale::whereDate('created_at', Carbon::today())
            ->where('type', 'production')
            ->count();

        // Today's bale count
        $todayBaleCount = Bale::whereDate('created_at', Carbon::today())
            ->where('type', 'production')
            ->whereHas('packinglist.product', function($q) {
                $q->where('type', 'bale');
            })
            ->count();

        // Today's jumbo count
        $todayJumboCount = Bale::whereDate('created_at', Carbon::today())
            ->where('type', 'production')
            ->whereHas('packinglist.product', function($q) {
                $q->where('type', 'jumbo');
            })
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

        // Delivered orders this month
        $thisMonthDelivered = Order::query()
            ->where('status', 'delivered')
            ->whereMonth('order_date', Carbon::now()->month)
            ->whereYear('order_date', Carbon::now()->year)
            ->latest('order_date')
            ->with('customer')
            ->take(5)
            ->get();

        // Delivered orders last month
        $lastMonthDelivered = Order::query()
            ->where('status', 'delivered')
            ->whereMonth('order_date', Carbon::now()->subMonth()->month)
            ->whereYear('order_date', Carbon::now()->subMonth()->year)
            ->latest('order_date')
            ->with('customer')
            ->take(5)
            ->get();

        // Upcoming orders (target date in future)
        $upcomingOrders = Order::query()
            ->where('status', 'production')
            ->where('target_date', '>=', Carbon::today())  // Only future dates
            ->orderBy('target_date', 'asc')                // Sort by target_date ascending
            ->with('customer')                             // Eager load customer relation
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'todayProduction',
            'todayBaleCount',
            'todayJumboCount',
            'todayRepacking',
            'recentActivities',
            'chartLabels',
            'chartData',
            'thisMonthDelivered',
            'lastMonthDelivered',
            'upcomingOrders'
        ));
    }
}