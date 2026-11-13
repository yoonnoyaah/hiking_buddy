<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GroupTrip;
use App\Models\TripMember;
use App\Models\Mountain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminOverviewController extends Controller
{
    public function index(Request $request)
    {
        // Admin access check
        if (
            !$request->user() ||
            strtolower((string) $request->user()->role) !== 'admin'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        // 1. Total registered users
        $totalUsers = User::count();

        // 2. Active trips scheduled for the current month
        $activeTripsThisMonth = GroupTrip::where('status', 'active')
            ->whereMonth('trip_date', now()->month)
            ->whereYear('trip_date', now()->year)
            ->count();

        // 3. Total active mountains
        $totalMountains = Mountain::where('is_active', true)->count();

        // 4. Total current member joins
        $totalJoins = TripMember::where('status', 'joined')->count();

        // 5. User growth over the last six months
        $startDate = Carbon::now()
            ->startOfMonth()
            ->subMonths(5);

        $userGrowth = User::selectRaw(
                "DATE_FORMAT(created_at, '%Y-%m') as month,
                 COUNT(*) as total"
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $growthLabels = [];
        $growthValues = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $startDate->copy()->addMonths($i);
            $monthKey = $month->format('Y-m');

            $growthLabels[] = $month->format('M');
            $growthValues[] = (int) (
                $userGrowth[$monthKey]->total ?? 0
            );
        }

        // 6. Most popular mountains
        // Includes both registered mountains and custom mountain names.
        $popularMountains = DB::table('group_trips as gt')
            ->leftJoin('mountains as m', 'gt.mountain_id', '=', 'm.id')
            ->leftJoin('trip_members as tm', function ($join) {
                $join->on('gt.id', '=', 'tm.trip_id')
                    ->where('tm.status', '=', 'joined');
            })
            ->where('gt.status', 'active')
            ->selectRaw("
                COALESCE(
                    NULLIF(gt.mountain_name, ''),
                    m.name,
                    'Unknown mountain'
                ) as mountain_name
            ")
            ->selectRaw('COUNT(DISTINCT gt.id) as trips_count')
            ->selectRaw('COUNT(tm.id) as joins_count')
            ->groupBy(
                'gt.mountain_name',
                'm.name'
            )
            ->orderByDesc('trips_count')
            ->orderByDesc('joins_count')
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,

            'statistics' => [
                'total_users' => $totalUsers,
                'active_trips_this_month' => $activeTripsThisMonth,
                'total_mountains' => $totalMountains,
                'total_joins' => $totalJoins,
            ],

            'user_growth' => [
                'labels' => $growthLabels,
                'data' => $growthValues,
            ],

            'popular_mountains' => $popularMountains,
        ]);
    }
}
