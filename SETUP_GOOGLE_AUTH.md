# 🔐 Setup Google Authentication untuk Approval System

## Langkah 1: Buat Google Cloud Project

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Buat project baru atau pilih project yang sudah ada
3. Enable **Google+ API** atau **People API**

## Langkah 2: Buat OAuth 2.0 Client ID

1. Buka [Credentials page](https://console.cloud.google.com/apis/credentials)
2. Click **Create Credentials** → **OAuth client ID**
3. Pilih **Application type**: **Web application**
4. Isi nama: `Starlink Management System`

### Configure OAuth Consent Screen (jika belum)

Sebelum bisa buat OAuth Client ID, Anda perlu configure consent screen:

1. Klik **Configure Consent Screen**
2. Pilih **External** (untuk testing) atau **Internal** (untuk organisasi)
3. Isi informasi:
   - App name: `Starlink Management System`
   - User support email: email Anda
   - Developer contact: email Anda
4. Save and Continue
5. Scopes: Tidak perlu tambah scope khusus (default sudah cukup)
6. Test users (untuk External): Tambahkan email yang boleh login

### Configure OAuth Client

5. **Authorized JavaScript origins**:
   ```
   https://datasl.octolink.id
   https://octolink.id
   http://localhost (untuk testing)
   ```

6. **Authorized redirect URIs**: (kosongkan untuk Sign-In button)

7. Click **Create**

## Langkah 3: Copy Client ID

1. Setelah dibuat, akan muncul popup dengan **Client ID**
2. Copy Client ID (format: `xxxxx-yyyyy.apps.googleusercontent.com`)
3. Paste ke file `index.html` line 5309:
   ```javascript
   GOOGLE_CLIENT_ID: 'YOUR_CLIENT_ID_HERE.apps.googleusercontent.com',
   ```

## Langkah 4: Test Authentication

1. Deploy file `index.html` yang sudah diupdate
2. Buka website Anda
3. Click tombol **Sign in with Google**
4. Akan muncul popup Google Sign-In
5. Pilih akun Google
6. Setelah berhasil, foto profil dan nama akan muncul

## Troubleshooting

### Error: "Invalid Client ID"
- Pastikan Client ID sudah benar di copy
- Pastikan domain website sudah ditambahkan di **Authorized JavaScript origins**

### Error: "Access blocked: This app's request is invalid"
- Configure OAuth Consent Screen
- Tambahkan email Anda sebagai test user (untuk External app)

### Google Sign-In library tidak load
- Check console browser untuk error
- Pastikan script `https://accounts.google.com/gsi/client` ter-load
- Clear cache browser

### One Tap tidak muncul
- Normal! One Tap hanya muncul di kondisi tertentu
- Pakai tombol "Sign in with Google" sebagai alternative
- One Tap mungkin di-block jika user sudah pernah dismiss

## Admin Configuration

Admin users dikonfigurasi di 2 tempat:

1. **Fallback di index.html** (line 5360):
   ```javascript
   ADMIN_EMAILS: [
       'baiiput@gmail.com',
       'admin@example.com' // Tambahkan admin lain
   ]
   ```

2. **Google Sheets** (recommended):
   - Buat sheet `Admin_Users` dengan kolom:
     - Column A: Email
     - Column B: Name
     - Column C: Role (isi: "Admin")
     - Column D: Added_Date

## Security Notes

⚠️ **PENTING**:
- Jangan share Client ID secret (kalau ada)
- Client ID publik (yang dipakai di frontend) boleh terlihat
- Set OAuth Consent Screen scope seminimal mungkin
- Gunakan HTTPS untuk production
- Review authorized origins secara berkala

## Deploy Checklist

✅ Client ID sudah dikonfigurasi di index.html
✅ Authorized JavaScript origins sudah ditambahkan
✅ Admin emails sudah dikonfigurasi
✅ File index.html sudah di-upload ke server
✅ Test login dengan akun Google
✅ Test approval flow (submit, approve, reject)
