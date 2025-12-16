# 🔐 Approval System Setup Guide

Complete guide untuk mengimplementasikan approval workflow system pada Starlink Management System.

---

## 📋 Table of Contents

1. [Google Sheets Setup](#1-google-sheets-setup)
2. [Google Apps Script Integration](#2-google-apps-script-integration)
3. [Admin Users Setup](#3-admin-users-setup)
4. [Frontend Configuration](#4-frontend-configuration)
5. [Testing Guide](#5-testing-guide)

---

## 1. Google Sheets Setup

### Step 1.1: Create New Sheets

Buka Google Spreadsheet yang sudah ada (`REPORT_SPREADSHEET_ID`), kemudian buat 3 sheet baru:

#### **Sheet 1: Pending_Approvals**

Buat sheet dengan nama **"Pending_Approvals"** dengan struktur kolom:

| Column | Header | Description |
|--------|--------|-------------|
| A | ID | Approval ID (auto-generated, format: PA-{timestamp}) |
| B | Row_ID | ID dari data yang diedit (ACC_NO) |
| C | Original_Data | Data asli dalam format JSON |
| D | New_Data | Data baru dalam format JSON |
| E | Changed_Columns | List kolom yang diubah (comma-separated) |
| F | Changed_By | Email user yang submit perubahan |
| G | Changed_By_Name | Nama user yang submit perubahan |
| H | Timestamp | Waktu submit perubahan |
| I | Status | Status approval (Pending/Approved/Rejected) |
| J | Reviewed_By | Email admin yang review |
| K | Review_Timestamp | Waktu review |
| L | Rejection_Reason | Alasan reject (jika ditolak) |
| M | Source_Sheet | Sheet sumber data (Client Aktif/Lepas/Non Aktif) |

**Formatting:**
- Row 1: Header dengan background biru (#4285f4), text putih, bold
- Freeze row 1

#### **Sheet 2: Edit_History**

Buat sheet dengan nama **"Edit_History"** dengan struktur kolom:

| Column | Header | Description |
|--------|--------|-------------|
| A | History_ID | History ID (auto-generated, format: HIS-{timestamp}) |
| B | Row_ID | ID dari data yang diedit |
| C | Action | Tipe action (Create/Update/Delete) |
| D | Old_Data | Data lama dalam format JSON |
| E | New_Data | Data baru dalam format JSON |
| F | Changed_By | Email user yang submit |
| G | Approved_By | Email admin yang approve |
| H | Timestamp | Waktu perubahan diapprove |
| I | Source_Sheet | Sheet sumber data |

**Formatting:**
- Row 1: Header dengan background hijau (#34a853), text putih, bold
- Freeze row 1

#### **Sheet 3: Admin_Users**

Buat sheet dengan nama **"Admin_Users"** dengan struktur kolom:

| Column | Header | Description |
|--------|--------|-------------|
| A | Email | Email admin (lowercase) |
| B | Name | Nama admin |
| C | Role | Role (Admin/Editor/Viewer) |
| D | Added_Date | Tanggal ditambahkan |

**Formatting:**
- Row 1: Header dengan background merah (#ea4335), text putih, bold
- Freeze row 1

**Example Data:**

| Email | Name | Role | Added_Date |
|-------|------|------|------------|
| admin@example.com | Admin User | Admin | 2025-12-16 |
| user@example.com | Regular User | Editor | 2025-12-16 |

---

## 2. Google Apps Script Integration

### Step 2.1: Open Apps Script Editor

1. Buka Google Spreadsheet
2. Klik **Extensions** → **Apps Script**
3. Akan terbuka Apps Script editor

### Step 2.2: Add Approval Extension Code

1. Di Apps Script editor, buat file baru atau gunakan file yang sudah ada
2. Copy semua kode dari file `appscript-approval-extension.js`
3. Paste ke Apps Script editor

### Step 2.3: Update Existing doPost Function

Tambahkan handler untuk approval actions di existing `doPost` function:

```javascript
function doPost(e) {
  try {
    Logger.log('=== DOPOST REQUEST ===');
    Logger.log('POST data: ' + e.postData.contents);

    var postData = JSON.parse(e.postData.contents);
    const action = postData.action;

    Logger.log('POST Action: ' + action);

    // ... existing actions (submitPaymentMultiKit, submitClientData, etc.) ...

    // ========================================
    // 🔐 NEW: APPROVAL SYSTEM ACTIONS
    // ========================================

    else if (action === 'addPendingApproval') {
      Logger.log('Adding pending approval...');
      const result = addPendingApproval(postData.approvalData);
      return ContentService.createTextOutput(JSON.stringify(result))
        .setMimeType(ContentService.MimeType.JSON);
    }

    else if (action === 'getPendingApprovals') {
      Logger.log('Getting pending approvals...');
      const result = getPendingApprovals(postData.filterStatus);
      return ContentService.createTextOutput(JSON.stringify(result))
        .setMimeType(ContentService.MimeType.JSON);
    }

    else if (action === 'approveChange') {
      Logger.log('Approving change...');
      const result = approveChange(postData.approvalId, postData.adminEmail);
      return ContentService.createTextOutput(JSON.stringify(result))
        .setMimeType(ContentService.MimeType.JSON);
    }

    else if (action === 'rejectChange') {
      Logger.log('Rejecting change...');
      const result = rejectChange(postData.approvalId, postData.adminEmail, postData.reason);
      return ContentService.createTextOutput(JSON.stringify(result))
        .setMimeType(ContentService.MimeType.JSON);
    }

    else if (action === 'approveAll') {
      Logger.log('Approving all changes...');
      const result = approveAll(postData.adminEmail);
      return ContentService.createTextOutput(JSON.stringify(result))
        .setMimeType(ContentService.MimeType.JSON);
    }

    else if (action === 'isAdmin') {
      Logger.log('Checking admin status...');
      const result = {
        isAdmin: isAdmin(postData.email),
        email: postData.email
      };
      return ContentService.createTextOutput(JSON.stringify(result))
        .setMimeType(ContentService.MimeType.JSON);
    }

    // ... rest of existing code ...

  } catch (error) {
    Logger.log('❌ Error in doPost: ' + error.message);
    return ContentService.createTextOutput(JSON.stringify({
      status: 'error',
      message: error.message
    })).setMimeType(ContentService.MimeType.JSON);
  }
}
```

### Step 2.4: Deploy as Web App

1. Click **Deploy** → **New deployment**
2. Settings:
   - **Type**: Web app
   - **Execute as**: Me
   - **Who has access**: Anyone
3. Click **Deploy**
4. Copy the **Web app URL** - ini akan digunakan di frontend
5. Format URL: `https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec`

### Step 2.5: Enable Gmail API (for Email Notifications)

1. Di Apps Script editor, click ⚙️ **Project Settings**
2. Scroll ke **Google Cloud Platform (GCP) Project**
3. Click project number link
4. Di Google Cloud Console:
   - Enable **Gmail API**
   - Enable **Google Sheets API**
5. Kembali ke Apps Script editor

---

## 3. Admin Users Setup

### Step 3.1: Add Admin Users

1. Buka sheet **"Admin_Users"**
2. Tambahkan baris baru untuk setiap admin:

```
| Email              | Name       | Role  | Added_Date |
|--------------------|------------|-------|------------|
| admin1@gmail.com   | Admin 1    | Admin | 2025-12-16 |
| admin2@gmail.com   | Admin 2    | Admin | 2025-12-16 |
```

**Important Notes:**
- Email harus **lowercase**
- Role harus exact: **"Admin"** (dengan capital A)
- Hanya user dengan Role = "Admin" yang bisa approve/reject

### Step 3.2: Test Admin Check

Test apakah admin check berfungsi:

1. Di Apps Script editor, buka **appscript-approval-extension.js**
2. Find function `isAdmin(email)`
3. Click **Run** → Select `isAdmin`
4. Di prompt, masukkan email admin
5. Check logs (View → Logs) - should return `true` for admin emails

---

## 4. Frontend Configuration

### Step 4.1: Update config.js

Tambahkan konfigurasi approval system di `config.js`:

```javascript
const CONFIG = {
  // ... existing config ...

  // ========================================
  // 🔐 APPROVAL SYSTEM CONFIG
  // ========================================

  // Google Apps Script URL for approval system
  APPROVAL_SCRIPT_URL: 'https://script.google.com/macros/s/YOUR_SCRIPT_ID/exec',

  // Google OAuth Client ID for Sign-In
  // Get this from: https://console.cloud.google.com/apis/credentials
  GOOGLE_CLIENT_ID: 'YOUR_CLIENT_ID.apps.googleusercontent.com',

  // Admin emails (fallback if Admin_Users sheet not available)
  ADMIN_EMAILS: [
    'admin1@gmail.com',
    'admin2@gmail.com'
  ],

  // Email notification settings
  EMAIL_NOTIFICATIONS: {
    enabled: true,
    sendToAdmin: true,
    sendToSubmitter: true
  },

  // Approval UI settings
  APPROVAL_UI: {
    enableEditMode: true,
    requireApproval: true,
    showApprovalQueue: true
  }
};
```

### Step 4.2: Get Google OAuth Client ID

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project (or create new)
3. Navigate to **APIs & Services** → **Credentials**
4. Click **+ CREATE CREDENTIALS** → **OAuth client ID**
5. Application type: **Web application**
6. Authorized JavaScript origins:
   - `http://localhost`
   - `https://yourdomain.com`
7. Click **Create**
8. Copy the **Client ID**
9. Paste ke `config.js` → `GOOGLE_CLIENT_ID`

### Step 4.3: Add Google Sign-In Library

Tambahkan di `<head>` section dari `data-clientv5.html`:

```html
<!-- Google Sign-In Library -->
<script src="https://accounts.google.com/gsi/client" async defer></script>
```

---

## 5. Testing Guide

### Step 5.1: Test Apps Script Functions

#### Test 1: Create Sheets
```javascript
// Run in Apps Script editor
function testCreateSheets() {
  const spreadsheet = getClientDataSpreadsheet();
  createPendingApprovalsSheet(spreadsheet);
  createEditHistorySheet(spreadsheet);
  createAdminUsersSheet(spreadsheet);
  Logger.log('✅ All sheets created successfully');
}
```

#### Test 2: Check Admin Status
```javascript
function testAdminCheck() {
  const email = 'admin1@gmail.com';
  const result = isAdmin(email);
  Logger.log('Is admin: ' + result);
}
```

#### Test 3: Add Pending Approval
```javascript
function testAddPendingApproval() {
  const approvalData = {
    rowId: 'SL00001',
    originalData: { O: 'Proses', M: 'Transfer' },
    newData: { O: 'Selesai', M: 'Cash' },
    changedColumns: ['O', 'M'],
    changedBy: 'user@example.com',
    changedByName: 'Test User',
    sourceSheet: 'Client Aktif'
  };

  const result = addPendingApproval(approvalData);
  Logger.log('Result: ' + JSON.stringify(result));
}
```

#### Test 4: Get Pending Approvals
```javascript
function testGetPendingApprovals() {
  const result = getPendingApprovals('Pending');
  Logger.log('Pending approvals: ' + JSON.stringify(result));
}
```

#### Test 5: Approve Change
```javascript
function testApproveChange() {
  const approvalId = 'PA-1234567890'; // Replace with actual ID
  const adminEmail = 'admin1@gmail.com';

  const result = approveChange(approvalId, adminEmail);
  Logger.log('Approve result: ' + JSON.stringify(result));
}
```

### Step 5.2: Test Email Notifications

1. Make sure Gmail API is enabled
2. Run `testAddPendingApproval()` function
3. Check admin email inbox - should receive notification
4. Approve the change using `testApproveChange()`
5. Check submitter email inbox - should receive approval notification

### Step 5.3: Test Frontend Integration

Will be covered after frontend implementation is complete.

---

## 📊 Data Flow Diagram

```
┌─────────────┐
│   User      │
│   (Editor)  │
└──────┬──────┘
       │
       │ 1. Edit Data
       │    via Modal
       ▼
┌─────────────────────┐
│  Submit for         │
│  Approval           │
└──────┬──────────────┘
       │
       │ 2. POST /exec
       │    action=addPendingApproval
       ▼
┌──────────────────────────────┐
│  Google Apps Script          │
│  - Validate data             │
│  - Add to Pending_Approvals  │
│  - Send email to admin       │
└──────┬───────────────────────┘
       │
       │ 3. Email Notification
       ▼
┌─────────────┐
│   Admin     │
│   (Approver)│
└──────┬──────┘
       │
       │ 4. Review in
       │    Approval Queue
       ▼
┌──────────────────┐
│ Approve/Reject   │
└──────┬───────────┘
       │
       │ 5. POST /exec
       │    action=approveChange/rejectChange
       ▼
┌──────────────────────────────┐
│  Google Apps Script          │
│  - Update main sheet (if approved)  │
│  - Update Pending_Approvals  │
│  - Log to Edit_History       │
│  - Send email to submitter   │
└──────────────────────────────┘
```

---

## ⚠️ Important Notes

### Security Considerations:

1. **Email Validation**: Apps Script akan check email di Admin_Users sheet
2. **Role Check**: Hanya user dengan Role = "Admin" yang bisa approve
3. **Audit Trail**: Semua perubahan logged di Edit_History
4. **Data Integrity**: Original data disimpan sebelum update

### Performance Tips:

1. **Batch Operations**: Use `approveAll()` untuk approve multiple changes sekaligus
2. **Caching**: Admin status di-cache untuk reduce lookup time
3. **Async Operations**: Email notifications dikirim asynchronously

### Common Issues & Solutions:

**Issue 1: "Admin_Users sheet not found"**
- **Solution**: Create the sheet manually atau run `createAdminUsersSheet()`

**Issue 2: "Email not sent"**
- **Solution**: Check Gmail API is enabled, check admin email is correct

**Issue 3: "Permission denied"**
- **Solution**: Re-authorize Apps Script, check deployment settings

**Issue 4: "Row not found for update"**
- **Solution**: Verify rowId (ACC_NO) exists di main sheet

---

## 🎯 Next Steps

After completing this setup:

1. ✅ Google Sheets structure created
2. ✅ Apps Script integrated and deployed
3. ✅ Admin users configured
4. ⏳ Frontend implementation (see frontend implementation guide)
5. ⏳ End-to-end testing
6. ⏳ Production deployment

---

## 📞 Support

Jika ada masalah atau pertanyaan:

1. Check Apps Script logs (View → Logs)
2. Check email delivery (Gmail → Sent)
3. Verify sheet structure matches documentation
4. Test individual functions in Apps Script editor

---

**Last Updated**: 2025-12-16
**Version**: 1.0.0
