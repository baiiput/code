-- Script untuk memperbaiki collation database
-- Jalankan script ini untuk memperbaiki error "Illegal mix of collations"

USE jimpitan_rt;

-- Set database collation
ALTER DATABASE jimpitan_rt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix tabel dawis
ALTER TABLE dawis CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix tabel warga
ALTER TABLE warga CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix tabel transaksi
ALTER TABLE transaksi CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix tabel pengeluaran
ALTER TABLE pengeluaran CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix tabel users
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Fix tabel settings
ALTER TABLE settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Recreate view progress_pembayaran_warga dengan COLLATE eksplisit
DROP VIEW IF EXISTS progress_pembayaran_warga;

CREATE VIEW progress_pembayaran_warga AS
SELECT
    w.id as warga_id,
    w.nama_lengkap,
    w.nomor_kk,
    w.alamat,
    w.no_telepon,
    w.status,
    w.dawis_id,
    d.nama_dawis,

    -- Get target tahunan dari settings
    (SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') as target_tahunan,

    -- Get tahun berjalan
    (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan') as tahun_berjalan,

    -- Hitung bulan berjalan (1-12)
    MONTH(CURRENT_DATE()) as bulan_berjalan,

    -- Target sampai bulan berjalan (target_tahunan * bulan_berjalan / 12)
    ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
          MONTH(CURRENT_DATE()) / 12, 0) as target_sampai_bulan_ini,

    -- Total setoran tahun ini
    COALESCE(SUM(CASE
        WHEN t.jenis_transaksi = 'setoran'
        AND YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
        THEN t.jumlah
        ELSE 0
    END), 0) as total_bayar_tahun_ini,

    -- Total penarikan tahun ini
    COALESCE(SUM(CASE
        WHEN t.jenis_transaksi = 'penarikan'
        AND YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
        THEN t.jumlah
        ELSE 0
    END), 0) as total_penarikan_tahun_ini,

    -- Saldo tahun ini (setoran - penarikan)
    COALESCE(SUM(CASE
        WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
        THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
        ELSE 0
    END), 0) as saldo_tahun_ini,

    -- Selisih dari target bulan ini
    COALESCE(SUM(CASE
        WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
        THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
        ELSE 0
    END), 0) - ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
          MONTH(CURRENT_DATE()) / 12, 0) as selisih_dari_target,

    -- Persentase pencapaian (saldo_tahun_ini / target_sampai_bulan_ini * 100)
    CASE
        WHEN ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
          MONTH(CURRENT_DATE()) / 12, 0) > 0
        THEN ROUND(
            (COALESCE(SUM(CASE
                WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
                THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
                ELSE 0
            END), 0) /
            ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
              MONTH(CURRENT_DATE()) / 12, 0)) * 100, 2)
        ELSE 0
    END as persentase_pencapaian,

    -- Status pembayaran (OK, Warning, Alert) - dengan COLLATE
    CASE
        WHEN ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
          MONTH(CURRENT_DATE()) / 12, 0) = 0 THEN 'ok'
        WHEN (COALESCE(SUM(CASE
                WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
                THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
                ELSE 0
            END), 0) /
            ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
              MONTH(CURRENT_DATE()) / 12, 0)) < 0.75 THEN 'alert'
        WHEN (COALESCE(SUM(CASE
                WHEN YEAR(t.tanggal_transaksi) = (SELECT CAST(setting_value AS UNSIGNED) FROM settings WHERE setting_key = 'tahun_berjalan')
                THEN CASE WHEN t.jenis_transaksi = 'setoran' THEN t.jumlah ELSE -t.jumlah END
                ELSE 0
            END), 0) /
            ROUND((SELECT CAST(setting_value AS DECIMAL(15,2)) FROM settings WHERE setting_key = 'target_tahunan') *
              MONTH(CURRENT_DATE()) / 12, 0)) < 0.90 THEN 'warning'
        ELSE 'ok'
    END as status_pembayaran

FROM warga w
LEFT JOIN dawis d ON w.dawis_id = d.id
LEFT JOIN transaksi t ON w.id = t.warga_id
WHERE w.status = 'aktif'
GROUP BY w.id, w.nama_lengkap, w.nomor_kk, w.alamat, w.no_telepon, w.status, w.dawis_id, d.nama_dawis;
