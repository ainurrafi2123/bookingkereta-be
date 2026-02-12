<?php

namespace App\Http\Controllers;

use App\Models\Kereta;
use App\Models\Gerbong;
use App\Models\User;
use App\Models\JadwalKereta;
use App\Models\PembelianTiket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    /**
     * Get dashboard statistics
     * 
     * @return JsonResponse
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            // Total trains
            $totalTrains = Kereta::count();

            // Total carriages
            $totalCarriages = Gerbong::count();

            // Total users
            $totalUsers = User::count();

            // Active schedules (today and future)
            $activeSchedules = JadwalKereta::where('tanggal_berangkat', '>=', Carbon::today())
                ->count();

            // Total bookings
            $totalBookings = PembelianTiket::count();

            // Total revenue
            $totalRevenue = PembelianTiket::where('status', 'booked')
                ->sum('total_harga');

            // Recent bookings (last 30 days)
            $recentBookings = PembelianTiket::where('tanggal_pembelian', '>=', Carbon::now()->subDays(30))
                ->count();

            // Booking status breakdown
            $bookedCount = PembelianTiket::where('status', 'booked')->count();
            $cancelledCount = PembelianTiket::where('status', 'cancelled')->count();

            // Calculate growth percentages (comparing last 30 days to previous 30 days)
            $previousMonthBookings = PembelianTiket::whereBetween('tanggal_pembelian', [
                Carbon::now()->subDays(60),
                Carbon::now()->subDays(30)
            ])->count();

            $bookingGrowth = $previousMonthBookings > 0 
                ? round((($recentBookings - $previousMonthBookings) / $previousMonthBookings) * 100, 1)
                : 0;

            // Revenue growth
            $previousMonthRevenue = PembelianTiket::where('status', 'booked')
                ->whereBetween('tanggal_pembelian', [
                    Carbon::now()->subDays(60),
                    Carbon::now()->subDays(30)
                ])
                ->sum('total_harga');

            $currentMonthRevenue = PembelianTiket::where('status', 'booked')
                ->where('tanggal_pembelian', '>=', Carbon::now()->subDays(30))
                ->sum('total_harga');

            $revenueGrowth = $previousMonthRevenue > 0
                ? round((($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 1)
                : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_trains' => $totalTrains,
                    'total_carriages' => $totalCarriages,
                    'total_users' => $totalUsers,
                    'active_schedules' => $activeSchedules,
                    'total_bookings' => $totalBookings,
                    'total_revenue' => (float) $totalRevenue,
                    'recent_bookings' => $recentBookings,
                    'booking_status' => [
                        'booked' => $bookedCount,
                        'cancelled' => $cancelledCount,
                    ],
                    'growth' => [
                        'bookings' => $bookingGrowth,
                        'revenue' => $revenueGrowth,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
