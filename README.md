# 🔒 Domain Masking / Reverse Proxy Setup

Panduan lengkap untuk menyembunyikan URL asli website dengan menggunakan domain dan hosting sendiri.

## 📋 Daftar Isi

- [Apa itu Domain Masking?](#apa-itu-domain-masking)
- [Metode yang Tersedia](#metode-yang-tersedia)
- [Perbandingan Metode](#perbandingan-metode)
- [Cara Setup](#cara-setup)
  - [1. Nginx Reverse Proxy](#1-nginx-reverse-proxy)
  - [2. Apache Reverse Proxy](#2-apache-reverse-proxy)
  - [3. PHP Proxy Script](#3-php-proxy-script)
- [Cloudflare sebagai Alternatif](#cloudflare-sebagai-alternatif)
- [FAQ](#faq)
- [Troubleshooting](#troubleshooting)

---

## Apa itu Domain Masking?

Domain masking adalah teknik untuk **menyembunyikan URL asli** suatu website dengan mengaksesnya melalui domain lain.

**Contoh:**
- URL Asli: `https://rahasia-website-saya.com`
- URL yang dilihat user: `https://domainanda.com`

User tidak akan tahu bahwa mereka sebenarnya mengakses `rahasia-website-saya.com`.

---

## Metode yang Tersedia

Repository ini menyediakan 3 metode:

1. **Nginx Reverse Proxy** (Paling efisien & cepat) ⚡
2. **Apache Reverse Proxy** (Alternatif untuk Apache) 🔄
3. **PHP Proxy Script** (Untuk shared hosting) 🐘

---

## Perbandingan Metode

| Metode | Kecepatan | Skalabilitas | Requirement | Cocok untuk |
|--------|-----------|--------------|-------------|-------------|
| **Nginx** | ⭐⭐⭐⭐⭐ | Sangat Tinggi | VPS/Dedicated | Production, High Traffic |
| **Apache** | ⭐⭐⭐⭐ | Tinggi | VPS/Dedicated | Production, Medium-High Traffic |
| **PHP Script** | ⭐⭐ | Rendah | Shared Hosting | Development, Low Traffic |

### Rekomendasi:
- **Punya VPS/Cloud Server?** → Gunakan **Nginx**
- **Pakai cPanel/Shared Hosting?** → Gunakan **PHP Script**
- **Website traffic tinggi?** → **Jangan** pakai PHP Script

---

## Cara Setup

### 1. Nginx Reverse Proxy

**✅ Keuntungan:**
- Sangat cepat dan efisien
- Handle traffic tinggi dengan baik
- Built-in caching
- Cocok untuk production

**📦 Requirements:**
- VPS atau Dedicated Server
- Akses root/sudo
- Nginx installed

**🔧 Langkah-langkah:**

```bash
# 1. Install Nginx (jika belum)
sudo apt update
sudo apt install nginx -y

# 2. Copy konfigurasi
sudo cp nginx-reverse-proxy.conf /etc/nginx/sites-available/proxy

# 3. Edit konfigurasi
sudo nano /etc/nginx/sites-available/proxy
```

Edit bagian berikut:
```nginx
server_name domainanda.com www.domainanda.com;  # Ganti dengan domain Anda
proxy_pass https://website-target.com;           # URL yang ingin di-mask
```

```bash
# 4. Enable site
sudo ln -s /etc/nginx/sites-available/proxy /etc/nginx/sites-enabled/

# 5. Test konfigurasi
sudo nginx -t

# 6. Restart Nginx
sudo systemctl restart nginx

# 7. (Opsional) Install SSL dengan Let's Encrypt
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d domainanda.com -d www.domainanda.com
```

---

### 2. Apache Reverse Proxy

**✅ Keuntungan:**
- Familiar bagi banyak developer
- Banyak modul tambahan tersedia
- Cocok untuk production

**📦 Requirements:**
- VPS atau Dedicated Server
- Akses root/sudo
- Apache installed

**🔧 Langkah-langkah:**

```bash
# 1. Install Apache (jika belum)
sudo apt update
sudo apt install apache2 -y

# 2. Enable modul yang diperlukan
sudo a2enmod proxy proxy_http ssl headers rewrite

# 3. Copy konfigurasi
sudo cp apache-reverse-proxy.conf /etc/apache2/sites-available/proxy.conf

# 4. Edit konfigurasi
sudo nano /etc/apache2/sites-available/proxy.conf
```

Edit bagian berikut:
```apache
ServerName domainanda.com                                    # Ganti dengan domain Anda
ProxyPass / https://website-target.com/                     # URL yang ingin di-mask
ProxyPassReverse / https://website-target.com/
```

```bash
# 5. Enable site
sudo a2ensite proxy.conf

# 6. Test konfigurasi
sudo apache2ctl configtest

# 7. Restart Apache
sudo systemctl restart apache2

# 8. (Opsional) Install SSL dengan Let's Encrypt
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d domainanda.com -d www.domainanda.com
```

---

### 3. PHP Proxy Script

**✅ Keuntungan:**
- Tidak butuh akses root
- Cocok untuk shared hosting
- Setup mudah

**⚠️ Kekurangan:**
- Lambat untuk traffic tinggi
- Konsumsi resource PHP lebih besar
- Tidak cocok untuk website dengan banyak asset

**📦 Requirements:**
- PHP 7.0+ dengan cURL extension
- Shared hosting / any hosting

**🔧 Langkah-langkah:**

1. **Edit file `proxy.php`:**

```php
// Ganti URL target
define('TARGET_URL', 'https://website-yang-ingin-di-mask.com');

// (Opsional) Whitelist domain
$allowed_domains = [
    'domainanda.com',
    'www.domainanda.com'
];
```

2. **Upload `proxy.php` ke root directory domain Anda**

3. **Buat file `.htaccess` di directory yang sama:**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ proxy.php [L,QSA]
```

4. **Upload `.htaccess`**

5. **Test akses domain Anda**

**Troubleshooting PHP Method:**
```bash
# Cek apakah cURL enabled
php -m | grep curl

# Jika tidak ada, enable di php.ini:
extension=curl
```

---

## Cloudflare sebagai Alternatif

**Cloudflare Workers** adalah alternatif modern yang sangat baik:

**✅ Keuntungan:**
- Gratis untuk basic usage
- Global CDN (sangat cepat)
- Tidak butuh server sendiri
- SSL otomatis

**📝 Contoh Cloudflare Worker:**

```javascript
addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
  const url = new URL(request.url)

  // Ganti dengan URL target
  const targetUrl = 'https://website-asli-yang-ingin-disembunyikan.com' + url.pathname + url.search

  // Clone request dan ganti URL
  const modifiedRequest = new Request(targetUrl, {
    method: request.method,
    headers: request.headers,
    body: request.body
  })

  // Fetch dari target
  const response = await fetch(modifiedRequest)

  // Clone response dan modifikasi headers jika perlu
  const modifiedResponse = new Response(response.body, response)

  return modifiedResponse
}
```

**Setup Cloudflare:**
1. Daftar di [Cloudflare](https://cloudflare.com)
2. Tambah domain Anda
3. Buka Workers
4. Buat Worker baru, paste code di atas
5. Deploy ke domain Anda

---

## FAQ

### Q: Apakah metode ini legal?
**A:** Ya, selama Anda punya hak untuk mengakses dan menampilkan konten tersebut. Jangan gunakan untuk menyembunyikan konten ilegal atau melanggar copyright.

### Q: Apakah website asli tahu bahwa kontennya di-proxy?
**A:** Tergantung konfigurasi. Mereka bisa melihat IP server proxy Anda di logs. Jika perlu anonymity penuh, kombinasikan dengan VPN/Proxy tambahan.

### Q: Apakah bisa untuk website yang ada login?
**A:** Bisa, tapi bisa rumit karena cookies dan session. Nginx/Apache lebih baik untuk kasus ini.

### Q: Kenapa gambar/CSS tidak muncul?
**A:** Kemungkinan URL-nya masih hard-coded ke domain asli. Gunakan metode Nginx/Apache yang bisa handle ini dengan lebih baik, atau modify response di PHP untuk rewrite URLs.

### Q: Apakah bisa untuk streaming video?
**A:** Kurang cocok dengan PHP method. Gunakan Nginx dengan proper buffering settings.

---

## Troubleshooting

### Problem: 502 Bad Gateway (Nginx/Apache)
**Solusi:**
```bash
# Cek apakah target URL bisa diakses
curl -I https://website-target.com

# Cek error log
sudo tail -f /var/log/nginx/proxy_error.log  # Nginx
sudo tail -f /var/log/apache2/proxy_error.log  # Apache
```

### Problem: SSL/HTTPS Error
**Solusi:**
```nginx
# Tambahkan di config Nginx
proxy_ssl_verify off;  # Untuk testing, JANGAN di production

# Atau install SSL certificate yang proper
```

### Problem: Redirect Loop
**Solusi:**
- Pastikan domain proxy BERBEDA dari domain target
- Cek `proxy_redirect` settings
- Disable redirect di target URL jika memungkinkan

### Problem: Slow Performance (PHP)
**Solusi:**
- Upgrade ke Nginx/Apache method
- Aktifkan caching di PHP
- Gunakan CDN seperti Cloudflare
- Optimize `curl_setopt` timeout settings

### Problem: CORS Errors
**Solusi:**
```nginx
# Tambahkan header CORS di Nginx
add_header Access-Control-Allow-Origin *;
add_header Access-Control-Allow-Methods "GET, POST, OPTIONS";
```

---

## 🔐 Security Notes

1. **Jangan proxy website tanpa izin** - bisa melanggar terms of service
2. **Enable SSL** - selalu gunakan HTTPS
3. **Rate limiting** - protect dari abuse
4. **Whitelist domain** - batasi akses jika perlu
5. **Monitor logs** - cek aktivitas mencurigakan

---

## 📞 Support

Jika ada pertanyaan atau issues:
1. Cek dokumentasi di atas
2. Cek troubleshooting section
3. Buka issue di repository ini

---

## 📄 License

Kode ini disediakan "as-is" untuk tujuan edukasi. Gunakan dengan bijak dan bertanggung jawab.

---

**Happy Proxying! 🚀**
