@extends('layouts.app')

@section('title', 'Detail Pelanggan')
@section('header', 'Detail Pelanggan')

@section('content')
<div class="space-y-6">
    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('customers.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Kembali
        </a>
        @if(Auth::user()->canManageCustomers())
        <a href="{{ route('customers.edit', $customer) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
            </svg>
            Edit
        </a>
        @endif
    </div>

    <!-- Customer Info -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $customer->nama }}</h3>
                @php
                    $statusColors = [
                        'aktif' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                        'nonaktif' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                        'lunas' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                        'belum_bayar' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    ];
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$customer->status_langganan] }}">
                    {{ ucfirst(str_replace('_', ' ', $customer->status_langganan)) }}
                </span>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Basic Info -->
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Informasi Dasar</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Client</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->email_client ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nomor CS</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->nomor_cs ?? '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Alamat</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->alamat ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Login Credentials -->
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Login Credentials</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Gmail</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $customer->gmail_email ?? '-' }}
                            @if($customer->gmail_password)
                            <br><span class="text-gray-500">Pass: {{ $customer->gmail_password }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Starlink</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $customer->starlink_email ?? '-' }}
                            @if($customer->starlink_password)
                            <br><span class="text-gray-500">Pass: {{ $customer->starlink_password }}</span>
                            @endif
                        </dd>
                    </div>
                    @if($customer->login_alternatif)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Login Alternatif</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $customer->login_alternatif }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Starlink Info -->
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Informasi Starlink</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ACC No.</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->acc_no ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">KIT Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->kit_number ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Serial Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->serial_number ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kode</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->kode ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last 4 Digit</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->last_4_digit ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">No Aktivasi</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->no_aktivasi ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Koordinat</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->koordinat_lokasi ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Subscription Info -->
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Informasi Langganan</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Paket</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $customer->paket ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Jatuh Tempo</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $customer->tanggal_jatuh_tempo ? $customer->tanggal_jatuh_tempo->format('d M Y') : '-' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Notes -->
            @if($customer->catatan)
            <div>
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Catatan</h4>
                <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $customer->catatan }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Payment History -->
    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">History Pembayaran</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($customer->payments as $payment)
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $payment->periode_bulan }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Diinput oleh: {{ $payment->user->name }}</p>
                        @if($payment->keterangan)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->keterangan }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Rp {{ number_format($payment->nominal, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment->tanggal_bayar->format('d M Y') }}</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum ada history pembayaran
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
