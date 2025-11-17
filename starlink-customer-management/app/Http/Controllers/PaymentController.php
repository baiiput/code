<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'user']);

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by period
        if ($request->filled('periode')) {
            $query->where('periode_bulan', $request->periode);
        }

        // Filter by date range
        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_bayar', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_bayar', '<=', $request->sampai_tanggal);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
        $customers = Customer::orderBy('nama')->get();
        $periodes = Payment::select('periode_bulan')->distinct()->orderBy('periode_bulan', 'desc')->pluck('periode_bulan');

        return view('payments.index', compact('payments', 'customers', 'periodes'));
    }

    public function create()
    {
        $customers = Customer::orderBy('nama')->get();
        return view('payments.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'nominal' => 'required|numeric|min:0',
            'periode_bulan' => 'required|string|max:255',
            'tanggal_bayar' => 'required|date',
            'keterangan' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        Payment::create($validated);

        // Update customer status to 'lunas'
        $customer = Customer::find($validated['customer_id']);
        $customer->update(['status_langganan' => 'lunas']);

        return redirect()->route('payments.index')
            ->with('success', 'Pembayaran berhasil dicatat dan status pelanggan diperbarui menjadi LUNAS.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer', 'user']);
        return view('payments.show', compact('payment'));
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()->route('payments.index')
            ->with('success', 'Data pembayaran berhasil dihapus.');
    }
}
