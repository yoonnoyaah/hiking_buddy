<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GroupTrip;
use App\Models\TripMember;
use App\Models\Mountain;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOverviewController extends Controller
{
    public function index(Request $request)
    {
        // Check admin access
        if (
            !$request->user() ||
            strtolower((string) $request->user()->role) !== 'admin'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        // 1. Dashboard statistics
        $totalUsers = User::count();

        $activeTripsThisMonth = GroupTrip::where('status', 'active')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalMountains = Mountain::where('is_active', true)->count();

        $totalJoins = TripMember::where('status', 'joined')->count();

        // 2. User growth - last 6 months
        $userGrowth = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'month' => $date->format('M'),
                'users' => User::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count()
            ];
        })->values();

        // 3. Most popular mountains - based on active trips
        $popularMountains = GroupTrip::where('status', 'active')
            ->select('mountain_name', DB::raw('COUNT(*) as total_trips'))
            ->groupBy('mountain_name')
            ->orderByDesc('total_trips')
            ->limit(6)
            ->get()
            ->map(function ($item) {
                return [
                    'mountain' => $item->mountain_name,
                    'trips' => (int) $item->total_trips
                ];
            });

        // 4. Community health
        $activeMembers = TripMember::where('status', 'joined')
            ->distinct('user_id')
            ->count('user_id');

        $activeMemberPercentage = $totalUsers > 0
            ? round(($activeMembers / $totalUsers) * 100)
            : 0;

        // Count trips that have no pending reports
        $totalTrips = GroupTrip::count();

        $pendingTripReports = Report::where(
            'reportable_type',
            GroupTrip::class
        )
            ->where('status', 'pending')
            ->distinct('reportable_id')
            ->count('reportable_id');

        $unflaggedTrips = max(0, $totalTrips - $pendingTripReports);

        $unflaggedPercentage = $totalTrips > 0
            ? round(($unflaggedTrips / $totalTrips) * 100)
            : 100;

        return response()->json([
            'success' => true,

            'statistics' => [
                'total_users' => $totalUsers,
                'active_trips_this_month' => $activeTripsThisMonth,
                'total_mountains' => $totalMountains,
                'total_joins' => $totalJoins,
            ],

            'user_growth' => $userGrowth,

            'popular_mountains' => $popularMountains,

            'community_health' => [
                'active_members_percentage' => $activeMemberPercentage,
                'unflagged_trips_percentage' => $unflaggedPercentage,
            ],
        ]);
    }
}
