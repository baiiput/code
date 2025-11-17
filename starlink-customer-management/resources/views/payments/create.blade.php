@extends('layouts.app')

@section('title', 'Input Pembayaran')
@section('header', 'Input Pembayaran Baru')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('payments.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
            <div class="space-y-4">
                <div>
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Pelanggan <span class="text-red-500">*</span></label>
                    <select name="customer_id" id="customer_id" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="">Pilih Pelanggan</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->nama }} - {{ $customer->email_client ?? $customer->nomor_cs ?? 'N/A' }}
                        </option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nominal" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nominal (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="nominal" id="nominal" value="{{ old('nominal') }}" required min="0" step="0.01" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="0">
                    @error('nominal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="periode_bulan" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Periode Bulan <span class="text-red-500">*</span></label>
                    <input type="text" name="periode_bulan" id="periode_bulan" value="{{ old('periode_bulan', now()->format('F Y')) }}" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="November 2024">
                    @error('periode_bulan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tanggal_bayar" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Bayar <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal_bayar" id="tanggal_bayar" value="{{ old('tanggal_bayar', now()->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    @error('tanggal_bayar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Keterangan tambahan (opsional)">{{ old('keterangan') }}</textarea>
                    @error('keterangan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/50 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 dark:text-yellow-200">
                        Setelah menyimpan pembayaran, status pelanggan akan otomatis berubah menjadi <strong>LUNAS</strong>.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('payments.index') }}" class="text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Simpan Pembayaran
            </button>
        </div>
    </form>
</div>
@endsection
