# 🚀 Quick Start Guide

Panduan cepat untuk setup domain masking dalam 5 menit!

## Pilih Metode Anda

### 🎯 Saya Punya VPS/Cloud Server
**→ Gunakan Nginx (Paling Cepat!)**

```bash
# 1. Install Nginx
sudo apt update && sudo apt install nginx -y

# 2. Edit konfigurasi
sudo nano /etc/nginx/sites-available/proxy
```

Paste ini:
```nginx
server {
    listen 80;
    server_name domainanda.com;  # ← GANTI INI

    location / {
        proxy_pass https://website-target.com;  # ← GANTI INI
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

```bash
# 3. Enable & restart
sudo ln -s /etc/nginx/sites-available/proxy /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# 4. Done! Test di browser
```

---

### 🎯 Saya Pakai Shared Hosting (cPanel/etc)
**→ Gunakan PHP Script**

```bash
# 1. Edit proxy.php, ganti baris ini:
define('TARGET_URL', 'https://website-yang-ingin-di-mask.com');

# 2. Upload proxy.php ke public_html/

# 3. Buat file .htaccess dengan isi:
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ proxy.php [L,QSA]

# 4. Upload .htaccess

# 5. Done! Akses domain Anda
```

---

### 🎯 Saya Ingin Solusi Tercepat Tanpa Server
**→ Gunakan Cloudflare Workers (GRATIS!)**

1. Daftar di [Cloudflare](https://cloudflare.com) (gratis)
2. Tambah domain Anda
3. Buka **Workers & Pages** → **Create Worker**
4. Paste code dari `cloudflare-worker.js`
5. Edit baris ini:
   ```javascript
   const TARGET_URL = 'https://website-target.com';  // ← GANTI
   ```
6. **Deploy**
7. **Add Route**: `domainanda.com/*` → pilih worker Anda
8. **Done!** ✨

---

## 📊 Perbandingan Cepat

| Method | Setup Time | Speed | Free? |
|--------|------------|-------|-------|
| **Cloudflare** | ⏱️ 5 menit | ⚡⚡⚡⚡⚡ | ✅ (100k req/hari) |
| **Nginx** | ⏱️ 10 menit | ⚡⚡⚡⚡⚡ | ✅ (butuh VPS) |
| **PHP Script** | ⏱️ 3 menit | ⚡⚡ | ✅ |

---

## ✅ Checklist

Sebelum mulai, pastikan:

- [ ] Anda punya domain sendiri
- [ ] Domain sudah pointing ke server/cloudflare Anda
- [ ] Anda tahu URL yang ingin di-mask
- [ ] Anda punya akses ke hosting/server

---

## 🆘 Troubleshooting Cepat

**Problem: Tidak bisa akses**
- Cek apakah domain sudah pointing dengan benar (`ping domainanda.com`)
- Cek firewall: `sudo ufw allow 80/tcp`

**Problem: 502 Bad Gateway**
- Cek apakah target URL bisa diakses: `curl -I https://target.com`
- Cek logs: `sudo tail -f /var/log/nginx/error.log`

**Problem: Gambar/CSS tidak muncul**
- Gunakan Nginx/Cloudflare (lebih baik handle static files)
- Atau tambahkan URL rewriting

---

## 🎓 Baca Dokumentasi Lengkap

Untuk penjelasan detail, baca [README.md](README.md)

---

**Need help?** Buka issue di repository ini!
