<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'gmail_email',
        'gmail_password',
        'starlink_email',
        'starlink_password',
        'login_alternatif',
        'acc_no',
        'email_client',
        'nomor_cs',
        'alamat',
        'kit_number',
        'serial_number',
        'tanggal_jatuh_tempo',
        'kode',
        'status_langganan',
        'paket',
        'last_4_digit',
        'no_aktivasi',
        'koordinat_lokasi',
        'catatan',
    ];

    protected $casts = [
        'tanggal_jatuh_tempo' => 'date',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class)->orderBy('created_at', 'desc');
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function scopeAktif($query)
    {
        return $query->where('status_langganan', 'aktif');
    }

    public function scopeNonaktif($query)
    {
        return $query->where('status_langganan', 'nonaktif');
    }

    public function scopeLunas($query)
    {
        return $query->where('status_langganan', 'lunas');
    }

    public function scopeBelumBayar($query)
    {
        return $query->where('status_langganan', 'belum_bayar');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama', 'like', "%{$search}%")
              ->orWhere('email_client', 'like', "%{$search}%")
              ->orWhere('nomor_cs', 'like', "%{$search}%")
              ->orWhere('kit_number', 'like', "%{$search}%")
              ->orWhere('serial_number', 'like', "%{$search}%")
              ->orWhere('acc_no', 'like', "%{$search}%");
        });
    }
}
