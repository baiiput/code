<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('gmail_email')->nullable();
            $table->string('gmail_password')->nullable();
            $table->string('starlink_email')->nullable();
            $table->string('starlink_password')->nullable();
            $table->text('login_alternatif')->nullable();
            $table->string('acc_no')->nullable();
            $table->string('email_client')->nullable();
            $table->string('nomor_cs')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kit_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->string('kode')->nullable();
            $table->enum('status_langganan', ['aktif', 'nonaktif', 'lunas', 'belum_bayar'])->default('aktif');
            $table->string('paket')->nullable();
            $table->string('last_4_digit')->nullable();
            $table->string('no_aktivasi')->nullable();
            $table->string('koordinat_lokasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
