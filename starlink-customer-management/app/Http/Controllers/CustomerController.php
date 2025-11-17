<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status_langganan', $request->status);
        }

        // Filter by paket
        if ($request->filled('paket')) {
            $query->where('paket', $request->paket);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);
        $pakets = Customer::select('paket')->distinct()->whereNotNull('paket')->pluck('paket');

        return view('customers.index', compact('customers', 'pakets'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'gmail_email' => 'nullable|email|max:255',
            'gmail_password' => 'nullable|string|max:255',
            'starlink_email' => 'nullable|email|max:255',
            'starlink_password' => 'nullable|string|max:255',
            'login_alternatif' => 'nullable|string',
            'acc_no' => 'nullable|string|max:255',
            'email_client' => 'nullable|email|max:255',
            'nomor_cs' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kit_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'tanggal_jatuh_tempo' => 'nullable|date',
            'kode' => 'nullable|string|max:255',
            'status_langganan' => 'required|in:aktif,nonaktif,lunas,belum_bayar',
            'paket' => 'nullable|string|max:255',
            'last_4_digit' => 'nullable|string|max:4',
            'no_aktivasi' => 'nullable|string|max:255',
            'koordinat_lokasi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Customer $customer)
    {
        $customer->load('payments.user');
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'gmail_email' => 'nullable|email|max:255',
            'gmail_password' => 'nullable|string|max:255',
            'starlink_email' => 'nullable|email|max:255',
            'starlink_password' => 'nullable|string|max:255',
            'login_alternatif' => 'nullable|string',
            'acc_no' => 'nullable|string|max:255',
            'email_client' => 'nullable|email|max:255',
            'nomor_cs' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kit_number' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'tanggal_jatuh_tempo' => 'nullable|date',
            'kode' => 'nullable|string|max:255',
            'status_langganan' => 'required|in:aktif,nonaktif,lunas,belum_bayar',
            'paket' => 'nullable|string|max:255',
            'last_4_digit' => 'nullable|string|max:4',
            'no_aktivasi' => 'nullable|string|max:255',
            'koordinat_lokasi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
    }
}
