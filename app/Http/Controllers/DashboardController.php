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

        return view('dashboard', compact(
            'todayProduction',
            'todayRepacking',
            'recentActivities',
            'chartLabels',
            'chartData'
        ));
    }
}