<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::aktif()->count();
        $inactiveCustomers = Customer::nonaktif()->count();
        $paidCustomers = Customer::lunas()->count();
        $unpaidCustomers = Customer::belumBayar()->count();

        // Get recent payments
        $recentPayments = Payment::with(['customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Get customers with upcoming due dates (next 7 days)
        $upcomingDue = Customer::where('tanggal_jatuh_tempo', '>=', now())
            ->where('tanggal_jatuh_tempo', '<=', now()->addDays(7))
            ->orderBy('tanggal_jatuh_tempo')
            ->take(10)
            ->get();

        // Monthly payment stats
        $currentMonth = now()->format('F Y');
        $monthlyPayments = Payment::where('periode_bulan', $currentMonth)->sum('nominal');
        $monthlyPaymentCount = Payment::where('periode_bulan', $currentMonth)->count();

        return view('dashboard.index', compact(
            'totalCustomers',
            'activeCustomers',
            'inactiveCustomers',
            'paidCustomers',
            'unpaidCustomers',
            'recentPayments',
            'upcomingDue',
            'monthlyPayments',
            'monthlyPaymentCount',
            'currentMonth'
        ));
    }
}
