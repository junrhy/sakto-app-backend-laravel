<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Inbox;
use App\Models\ClientDetails;
use App\Models\CreditHistory;
use App\Models\Credit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get credit overview data for the last 7 days
        $overview = CreditHistory::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_requests'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN package_credit ELSE 0 END) as approved_credits'),
            DB::raw('SUM(CASE WHEN status = "pending" THEN package_credit ELSE 0 END) as pending_credits')
        )
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total_requests' => $item->total_requests,
                    'approved_credits' => $item->approved_credits,
                    'pending_credits' => $item->pending_credits,
                ];
            });

        // Get recent credit requests
        $recentSales = CreditHistory::with(['client', 'credit'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'client_name' => $item->client->name ?? 'N/A',
                    'client_identifier' => $item->client->client_identifier ?? 'N/A',
                    'package_credit' => $item->package_credit ?? 'N/A',
                    'status' => $item->status ?? 'N/A',
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $stats = [
            'totalClients' => Client::count(),
            'activeClients' => Client::where('active', true)->count(),
            'totalMessages' => Inbox::count(),
            'unreadMessages' => Inbox::where('is_read', false)->count(),
            'totalClientDetails' => ClientDetails::count(),
            'pendingCreditRequests' => CreditHistory::where('status', 'pending')->count(),
            'totalCredits' => Credit::sum('available_credit'),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'overview' => $overview,
            'recentSales' => $recentSales
        ]);
    }
} 