<!-- Basic Info -->
<div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Dasar</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label for="nama" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nama <span class="text-red-500">*</span></label>
            <input type="text" name="nama" id="nama" value="{{ old('nama', $customer->nama ?? '') }}" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('nama') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email_client" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email Client</label>
            <input type="email" name="email_client" id="email_client" value="{{ old('email_client', $customer->email_client ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('email_client') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="nomor_cs" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nomor CS</label>
            <input type="text" name="nomor_cs" id="nomor_cs" value="{{ old('nomor_cs', $customer->nomor_cs ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('nomor_cs') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="alamat" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Alamat</label>
            <textarea name="alamat" id="alamat" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ old('alamat', $customer->alamat ?? '') }}</textarea>
            @error('alamat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<!-- Login Credentials -->
<div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Login Credentials</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="gmail_email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gmail Email</label>
            <input type="email" name="gmail_email" id="gmail_email" value="{{ old('gmail_email', $customer->gmail_email ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('gmail_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="gmail_password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gmail Password</label>
            <input type="text" name="gmail_password" id="gmail_password" value="{{ old('gmail_password', $customer->gmail_password ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('gmail_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="starlink_email" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Starlink Email</label>
            <input type="email" name="starlink_email" id="starlink_email" value="{{ old('starlink_email', $customer->starlink_email ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('starlink_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="starlink_password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Starlink Password</label>
            <input type="text" name="starlink_password" id="starlink_password" value="{{ old('starlink_password', $customer->starlink_password ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('starlink_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="login_alternatif" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Login Alternatif</label>
            <textarea name="login_alternatif" id="login_alternatif" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ old('login_alternatif', $customer->login_alternatif ?? '') }}</textarea>
            @error('login_alternatif') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<!-- Starlink Info -->
<div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Starlink</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label for="acc_no" class="block text-sm font-medium text-gray-700 dark:text-gray-200">ACC No.</label>
            <input type="text" name="acc_no" id="acc_no" value="{{ old('acc_no', $customer->acc_no ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('acc_no') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="kit_number" class="block text-sm font-medium text-gray-700 dark:text-gray-200">KIT Number</label>
            <input type="text" name="kit_number" id="kit_number" value="{{ old('kit_number', $customer->kit_number ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('kit_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="serial_number" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Serial Number</label>
            <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number', $customer->serial_number ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('serial_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="kode" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Kode</label>
            <input type="text" name="kode" id="kode" value="{{ old('kode', $customer->kode ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('kode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="last_4_digit" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Last 4 Digit</label>
            <input type="text" name="last_4_digit" id="last_4_digit" maxlength="4" value="{{ old('last_4_digit', $customer->last_4_digit ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('last_4_digit') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="no_aktivasi" class="block text-sm font-medium text-gray-700 dark:text-gray-200">No Aktivasi</label>
            <input type="text" name="no_aktivasi" id="no_aktivasi" value="{{ old('no_aktivasi', $customer->no_aktivasi ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('no_aktivasi') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="koordinat_lokasi" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Koordinat Lokasi</label>
            <input type="text" name="koordinat_lokasi" id="koordinat_lokasi" value="{{ old('koordinat_lokasi', $customer->koordinat_lokasi ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('koordinat_lokasi') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<!-- Subscription Info -->
<div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Langganan</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label for="paket" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Paket</label>
            <input type="text" name="paket" id="paket" value="{{ old('paket', $customer->paket ?? '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('paket') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="status_langganan" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status Langganan <span class="text-red-500">*</span></label>
            <select name="status_langganan" id="status_langganan" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                <option value="aktif" {{ old('status_langganan', $customer->status_langganan ?? 'aktif') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ old('status_langganan', $customer->status_langganan ?? '') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                <option value="lunas" {{ old('status_langganan', $customer->status_langganan ?? '') == 'lunas' ? 'selected' : '' }}>Lunas</option>
                <option value="belum_bayar" {{ old('status_langganan', $customer->status_langganan ?? '') == 'belum_bayar' ? 'selected' : '' }}>Belum Bayar</option>
            </select>
            @error('status_langganan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tanggal_jatuh_tempo" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Jatuh Tempo</label>
            <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo" value="{{ old('tanggal_jatuh_tempo', isset($customer->tanggal_jatuh_tempo) ? $customer->tanggal_jatuh_tempo->format('Y-m-d') : '') }}" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('tanggal_jatuh_tempo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<!-- Notes -->
<div class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Catatan</h3>
    <div>
        <textarea name="catatan" id="catatan" rows="3" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">{{ old('catatan', $customer->catatan ?? '') }}</textarea>
        @error('catatan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
</div>
