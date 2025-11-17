@extends('layouts.app')

@section('title', 'Edit Pelanggan')
@section('header', 'Edit Pelanggan')

@section('content')
<div class="max-w-4xl">
    <form action="{{ route('customers.update', $customer) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('customers._form')

        <div class="flex items-center justify-end gap-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('customers.index') }}" class="text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Update Pelanggan
            </button>
        </div>
    </form>
</div>
@endsection
