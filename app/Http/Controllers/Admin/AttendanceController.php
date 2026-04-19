<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        // Get total entry days (distinct dates in entire sales table)
        $totalEntryDays = Sale::distinct('date')
            ->count('date');

        // Get all sellers
        $sellers = Seller::all();

        // Build attendance data for each seller
        $attendanceData = [];
        $monthsWithEntries = [];

        foreach ($sellers as $seller) {
            // Get distinct dates where seller had sales (present days)
            $presentDays = Sale::where('seller_id', $seller->id)
                ->distinct('date')
                ->count('date');

            // Get month-wise attendance for current year (2026)
            $monthlyAttendance = [];
            for ($month = 1; $month <= 12; $month++) {
                $daysInMonth = Sale::where('seller_id', $seller->id)
                    ->whereYear('date', 2026)
                    ->whereMonth('date', $month)
                    ->distinct('date')
                    ->count('date');

                $monthlyAttendance[$month] = $daysInMonth;

                // Track which months have any entries
                if ($daysInMonth > 0) {
                    $monthsWithEntries[$month] = true;
                }
            }

            $attendanceData[] = [
                'seller' => $seller,
                'present_days' => $presentDays,
                'monthly' => $monthlyAttendance,
            ];
        }

        // Sort attendance data by present days (descending)
        usort($attendanceData, function ($a, $b) {
            return $b['present_days'] - $a['present_days'];
        });

        // Get sorted list of months with entries
        $activeMonths = array_keys($monthsWithEntries);

        // Get month names
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return view('admin.attendance.index', compact('attendanceData', 'totalEntryDays', 'months', 'activeMonths'));
    }
}
