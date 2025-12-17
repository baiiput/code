# 🔧 Troubleshooting CORS Error - Google Apps Script

## ❌ Error yang Muncul:

```
Access to fetch at 'https://script.google.com/macros/s/...'
from origin 'https://datasl.octolink.id'
has been blocked by CORS policy:
Response to preflight request doesn't pass access control check:
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

---

## ✅ Solusi Step-by-Step

### 1️⃣ **Verify Deployment Settings (PALING PENTING!)**

Buka Google Apps Script → **Deploy** → **Manage deployments**

Click **Edit** (⚙️ gear icon) pada deployment aktif.

**HARUS PERSIS SEPERTI INI:**

```
✅ Execute as: Me (baiiput@gmail.com)
   ↑ BUKAN "User accessing the web app"!

✅ Who has access: Anyone
   ↑ BUKAN "Only myself"!
```

**Jika salah, ubah dan click Update!**

---

### 2️⃣ **Test Deployment URL Langsung di Browser**

Buka URL deployment di browser (GET request):

```
https://script.google.com/macros/s/AKfycbwvZ2IGMisUjjymIco8eIdbrptMy8Nk_AeO8lLmoKN-OLXPHdC6JwK9D4uMAWygUTrV/exec?action=getAllClientDataComplete
```

**Expected Response:**
- ✅ JSON data muncul (atau error JSON, tapi bukan HTML redirect)
- ❌ Jika muncul Google login page → Settings salah!
- ❌ Jika muncul "Authorization required" → Settings salah!

**Jika muncul login page/authorization:**
→ Deployment settings masih salah, ubah ke "Execute as: Me" dan "Who has access: Anyone"

---

### 3️⃣ **Wait for Propagation (2-5 menit)**

Setelah deploy/update:
- Google perlu **2-5 menit** untuk propagate changes
- Clear browser cache: `Ctrl + Shift + R`
- Try in **Incognito window** (cache-free)
- Wait 5 menit, lalu test lagi

---

### 4️⃣ **Test dengan cURL (Bypass Browser CORS)**

Buka terminal/command prompt:

```bash
curl -X POST \
  'https://script.google.com/macros/s/AKfycbwvZ2IGMisUjjymIco8eIdbrptMy8Nk_AeO8lLmoKN-OLXPHdC6JwK9D4uMAWygUTrV/exec' \
  -H 'Content-Type: application/json' \
  -d '{"action":"isAdmin","email":"baiiput@gmail.com"}'
```

**Expected Response:**
```json
{"status":"success","isAdmin":true}
```

**Jika berhasil via cURL tapi gagal di browser:**
→ Masalah di browser cache, clear cache dan wait propagation

**Jika gagal via cURL juga:**
→ Script/deployment ada masalah

---

### 5️⃣ **Check Apps Script Execution Log**

Di Apps Script Editor:
1. Click **Executions** (⏱️ icon di sidebar)
2. Lihat apakah ada executions saat POST request
3. Check error messages

**Jika tidak ada executions sama sekali:**
→ Request tidak sampai ke script (deployment settings issue)

**Jika ada executions dengan error:**
→ Script ada bug, check error message

---

### 6️⃣ **Verify Script Code - doGet Handler**

Pastikan script punya `doGet()` function. Google Apps Script butuh ini untuk handle OPTIONS preflight.

Buka Apps Script Editor, search `function doGet`:

```javascript
function doGet(e) {
  // Should exist and return something
  // Even simple response is OK
}
```

**Jika tidak ada doGet:**
→ Copy lagi semua code dari `appscript.txt`

---

### 7️⃣ **Create TEST Deployment**

Buat deployment terpisah untuk testing:

1. Apps Script Editor → **Deploy** → **Test deployments**
2. Click **Select type** → **Web app**
3. Execute as: **Me**
4. Click **Deploy**
5. Test dengan URL test deployment

**Jika test deployment works tapi production deployment tidak:**
→ Production deployment settings salah, perlu update/redeploy

---

### 8️⃣ **Nuclear Option - Delete & Redeploy**

Jika semua cara di atas gagal:

1. **Delete ALL deployments**:
   - Deploy → Manage deployments
   - Click 🗑️ Archive untuk setiap deployment

2. **Create Fresh Deployment**:
   - Deploy → New deployment
   - Select type → Web app
   - **Execute as: Me** ⚠️
   - **Who has access: Anyone** ⚠️
   - Click Deploy

3. **Authorize** (first time):
   - Review permissions
   - Click Allow
   - Might see "Google hasn't verified this app" → Click Advanced → Go to [Project] (unsafe)

4. **Copy NEW URL** → Update config.js

5. **Wait 5 minutes** for propagation

6. **Test**

---

## 🎯 Quick Checklist

Centang satu-satu:

- [ ] Deployment: Execute as = **Me** (not User)
- [ ] Deployment: Who has access = **Anyone** (not Only myself)
- [ ] URL deployment sudah di-update di config.js
- [ ] config.js sudah di-upload ke server
- [ ] Clear browser cache (Ctrl+Shift+R)
- [ ] Wait 5 menit setelah deploy
- [ ] Test GET request di browser langsung (lihat point 2)
- [ ] Jika GET works, test POST via cURL (lihat point 4)
- [ ] Check Apps Script Executions log

---

## 📞 Common Mistakes

### ❌ Mistake 1: "Execute as: User"
```
Execute as: User accessing the web app  ← SALAH!
```
**Fix:** Ubah ke "Me (your-email@gmail.com)"

### ❌ Mistake 2: "Who has access: Only myself"
```
Who has access: Only myself  ← SALAH untuk public app!
```
**Fix:** Ubah ke "Anyone"

### ❌ Mistake 3: Lupa Authorize
Saat first deployment, Google minta authorize permissions.
**Fix:** Click Advanced → Go to [Project] (unsafe) → Allow

### ❌ Mistake 4: Tidak Wait Propagation
Deploy → Langsung test → CORS error
**Fix:** Wait 5 menit, clear cache, test lagi

### ❌ Mistake 5: Wrong URL in config.js
config.js masih pakai URL lama
**Fix:** Update config.js dengan URL deployment terbaru

---

## 🔍 Advanced Debugging

### Check Response Headers

Buka Chrome DevTools → Network tab → Click failed request

Check **Response Headers** (harusnya ada tapi tidak muncul = CORS blocked):
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, OPTIONS
```

Jika headers tidak ada = Deployment settings salah atau propagation belum selesai.

### Check Request Method

Di Network tab, lihat **Request Method**:
- ❌ OPTIONS (failed) = Preflight blocked = CORS issue
- ✅ POST (success) = All good

---

## 💡 Why CORS Happens

Browser security: Different origin (datasl.octolink.id) calling different origin (script.google.com).

**Browser sends:**
1. OPTIONS request (preflight) → "Can I POST here?"
2. If OK → POST request (actual data)

**If deployment settings wrong:**
- Google Apps Script doesn't respond to OPTIONS
- Browser blocks POST
- CORS error

**Fix = Deployment settings!**

---

## ✅ Success Indicators

Setelah fix, harusnya lihat di console:

```
✅ Admin status verified
✅ Pending approvals loaded: 0 items
✏️ Edit button shown (user authenticated)
```

**No CORS errors!** 🎉

---

## 📚 Resources

- [Google Apps Script Web Apps](https://developers.google.com/apps-script/guides/web)
- [Understanding CORS](https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS)
- [Apps Script Authorization](https://developers.google.com/apps-script/guides/services/authorization)

---

**Last Resort:** Screenshot deployment settings, script code, and error message → Ask di Google Apps Script Community or Stack Overflow.
