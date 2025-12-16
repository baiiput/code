// ========================================
// 🔐 APPROVAL SYSTEM EXTENSION
// ========================================
// This extension adds approval workflow functionality to the Starlink Management System
// Features: Edit approval, email notifications, admin roles, audit history
// ========================================

// ========================================
// 📋 1. APPROVAL CONFIGURATION
// ========================================

const APPROVAL_CONFIG = {
  // Sheet names for approval system
  PENDING_APPROVALS_SHEET: 'Pending_Approvals',
  EDIT_HISTORY_SHEET: 'Edit_History',
  ADMIN_USERS_SHEET: 'Admin_Users',

  // Email configuration
  EMAIL_FROM_NAME: 'Starlink Management System',
  EMAIL_NOTIFICATIONS_ENABLED: true,

  // Approval statuses
  STATUS: {
    PENDING: 'Pending',
    APPROVED: 'Approved',
    REJECTED: 'Rejected'
  }
};

// ========================================
// 🛠️ 2. HELPER FUNCTIONS
// ========================================

/**
 * Get the main client data spreadsheet
 */
function getClientDataSpreadsheet() {
  // Use the existing REPORT_SPREADSHEET_ID or modify as needed
  return SpreadsheetApp.openById(REPORT_SPREADSHEET_ID);
}

/**
 * Generate unique approval ID
 */
function generateApprovalID() {
  const timestamp = new Date().getTime();
  return 'PA-' + timestamp;
}

/**
 * Format date for display
 */
function formatDateTime(date) {
  if (!date || !(date instanceof Date)) return '-';
  const options = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  };
  return new Intl.DateTimeFormat('id-ID', options).format(date);
}

/**
 * Check if user is admin
 */
function isAdmin(email) {
  try {
    const spreadsheet = getClientDataSpreadsheet();
    const adminSheet = spreadsheet.getSheetByName(APPROVAL_CONFIG.ADMIN_USERS_SHEET);

    if (!adminSheet) {
      Logger.log('⚠️ Admin_Users sheet not found, using fallback');
      // Fallback: return true for specific emails
      const fallbackAdmins = ['admin@example.com']; // TODO: Update with actual admin emails
      return fallbackAdmins.includes(email.toLowerCase());
    }

    const data = adminSheet.getDataRange().getValues();

    // Skip header row (row 0)
    for (let i = 1; i < data.length; i++) {
      const adminEmail = data[i][0]; // Column A: Email
      const role = data[i][2]; // Column C: Role

      if (adminEmail && adminEmail.toLowerCase() === email.toLowerCase() && role === 'Admin') {
        return true;
      }
    }

    return false;
  } catch (error) {
    Logger.log('❌ Error checking admin status: ' + error.message);
    return false;
  }
}

/**
 * Get user info from session
 */
function getCurrentUserInfo() {
  try {
    const user = Session.getActiveUser();
    return {
      email: user.getEmail(),
      isAdmin: isAdmin(user.getEmail())
    };
  } catch (error) {
    Logger.log('❌ Error getting user info: ' + error.message);
    return {
      email: '',
      isAdmin: false
    };
  }
}

// ========================================
// 📝 3. PENDING APPROVALS FUNCTIONS
// ========================================

/**
 * Add new pending approval
 * POST /exec?action=addPendingApproval
 */
function addPendingApproval(approvalData) {
  try {
    Logger.log('📝 Adding pending approval...');

    const spreadsheet = getClientDataSpreadsheet();
    let pendingSheet = spreadsheet.getSheetByName(APPROVAL_CONFIG.PENDING_APPROVALS_SHEET);

    // Create sheet if doesn't exist
    if (!pendingSheet) {
      Logger.log('Creating Pending_Approvals sheet...');
      pendingSheet = createPendingApprovalsSheet(spreadsheet);
    }

    // Generate approval ID
    const approvalId = generateApprovalID();
    const timestamp = new Date();

    // Prepare row data
    const rowData = [
      approvalId,                                    // A: ID
      approvalData.rowId || '',                      // B: Row_ID
      JSON.stringify(approvalData.originalData),     // C: Original_Data
      JSON.stringify(approvalData.newData),          // D: New_Data
      approvalData.changedColumns.join(', '),        // E: Changed_Columns
      approvalData.changedBy || '',                  // F: Changed_By (email)
      approvalData.changedByName || '',              // G: Changed_By_Name
      timestamp,                                     // H: Timestamp
      APPROVAL_CONFIG.STATUS.PENDING,                // I: Status
      '',                                            // J: Reviewed_By
      '',                                            // K: Review_Timestamp
      '',                                            // L: Rejection_Reason
      approvalData.sourceSheet || 'Client Aktif'     // M: Source_Sheet
    ];

    // Append row
    pendingSheet.appendRow(rowData);

    Logger.log('✅ Pending approval added: ' + approvalId);

    // Send email notification to admin
    if (APPROVAL_CONFIG.EMAIL_NOTIFICATIONS_ENABLED) {
      sendNewApprovalNotification(approvalId, approvalData);
    }

    return {
      status: 'success',
      message: 'Perubahan berhasil dikirim untuk approval',
      approvalId: approvalId
    };

  } catch (error) {
    Logger.log('❌ Error adding pending approval: ' + error.message);
    return {
      status: 'error',
      message: 'Gagal menambahkan pending approval: ' + error.message
    };
  }
}

/**
 * Get all pending approvals
 * GET /exec?action=getPendingApprovals
 */
function getPendingApprovals(filterStatus) {
  try {
    Logger.log('📋 Getting pending approvals...');

    const spreadsheet = getClientDataSpreadsheet();
    const pendingSheet = spreadsheet.getSheetByName(APPROVAL_CONFIG.PENDING_APPROVALS_SHEET);

    if (!pendingSheet) {
      return {
        status: 'success',
        data: [],
        message: 'No pending approvals sheet found'
      };
    }

    const data = pendingSheet.getDataRange().getValues();
    const approvals = [];

    // Skip header row (row 0)
    for (let i = 1; i < data.length; i++) {
      const row = data[i];
      const status = row[8]; // Column I: Status

      // Filter by status if provided
      if (filterStatus && status !== filterStatus) {
        continue;
      }

      approvals.push({
        approvalId: row[0],
        rowId: row[1],
        originalData: JSON.parse(row[2] || '{}'),
        newData: JSON.parse(row[3] || '{}'),
        changedColumns: row[4] ? row[4].split(', ') : [],
        changedBy: row[5],
        changedByName: row[6],
        timestamp: row[7],
        status: row[8],
        reviewedBy: row[9],
        reviewTimestamp: row[10],
        rejectionReason: row[11],
        sourceSheet: row[12] || 'Client Aktif',
        rowIndex: i + 1 // Store row index for later updates
      });
    }

    Logger.log('✅ Found ' + approvals.length + ' pending approvals');

    return {
      status: 'success',
      data: approvals,
      count: approvals.length
    };

  } catch (error) {
    Logger.log('❌ Error getting pending approvals: ' + error.message);
    return {
      status: 'error',
      message: 'Gagal mengambil pending approvals: ' + error.message,
      data: []
    };
  }
}

/**
 * Approve a pending change
 * POST /exec?action=approveChange
 */
function approveChange(approvalId, adminEmail) {
  try {
    Logger.log('✅ Approving change: ' + approvalId);

    const spreadsheet = getClientDataSpreadsheet();
    const pendingSheet = spreadsheet.getSheetByName(APPROVAL_CONFIG.PENDING_APPROVALS_SHEET);

    if (!pendingSheet) {
      return {
        status: 'error',
        message: 'Pending approvals sheet not found'
      };
    }

    // Find the approval
    const data = pendingSheet.getDataRange().getValues();
    let approvalRow = null;
    let rowIndex = -1;

    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === approvalId) {
        approvalRow = data[i];
        rowIndex = i + 1; // Sheet rows are 1-indexed
        break;
      }
    }

    if (!approvalRow) {
      return {
        status: 'error',
        message: 'Approval tidak ditemukan'
      };
    }

    // Check if already approved/rejected
    if (approvalRow[8] !== APPROVAL_CONFIG.STATUS.PENDING) {
      return {
        status: 'error',
        message: 'Approval sudah di-' + approvalRow[8].toLowerCase()
      };
    }

    // Parse data
    const rowId = approvalRow[1];
    const newData = JSON.parse(approvalRow[3]);
    const sourceSheet = approvalRow[12] || 'Client Aktif';
    const changedBy = approvalRow[5];
    const changedByName = approvalRow[6];

    // Update the main data sheet
    const updateResult = updateClientData(sourceSheet, rowId, newData);

    if (updateResult.status !== 'success') {
      return updateResult;
    }

    // Update approval status
    const reviewTimestamp = new Date();
    pendingSheet.getRange(rowIndex, 9).setValue(APPROVAL_CONFIG.STATUS.APPROVED); // Status
    pendingSheet.getRange(rowIndex, 10).setValue(adminEmail); // Reviewed_By
    pendingSheet.getRange(rowIndex, 11).setValue(reviewTimestamp); // Review_Timestamp

    // Log to history
    logToHistory({
      rowId: rowId,
      action: 'Update',
      oldData: JSON.parse(approvalRow[2]),
      newData: newData,
      changedBy: changedBy,
      approvedBy: adminEmail,
      sourceSheet: sourceSheet
    });

    // Send approval notification email
    if (APPROVAL_CONFIG.EMAIL_NOTIFICATIONS_ENABLED) {
      sendApprovalNotification(approvalId, changedBy, changedByName, adminEmail, true);
    }

    Logger.log('✅ Change approved successfully');

    return {
      status: 'success',
      message: 'Perubahan berhasil diapprove dan data sudah diupdate'
    };

  } catch (error) {
    Logger.log('❌ Error approving change: ' + error.message);
    return {
      status: 'error',
      message: 'Gagal approve change: ' + error.message
    };
  }
}

/**
 * Reject a pending change
 * POST /exec?action=rejectChange
 */
function rejectChange(approvalId, adminEmail, reason) {
  try {
    Logger.log('❌ Rejecting change: ' + approvalId);

    const spreadsheet = getClientDataSpreadsheet();
    const pendingSheet = spreadsheet.getSheetByName(APPROVAL_CONFIG.PENDING_APPROVALS_SHEET);

    if (!pendingSheet) {
      return {
        status: 'error',
        message: 'Pending approvals sheet not found'
      };
    }

    // Find the approval
    const data = pendingSheet.getDataRange().getValues();
    let approvalRow = null;
    let rowIndex = -1;

    for (let i = 1; i < data.length; i++) {
      if (data[i][0] === approvalId) {
        approvalRow = data[i];
        rowIndex = i + 1;
        break;
      }
    }

    if (!approvalRow) {
      return {
        status: 'error',
        message: 'Approval tidak ditemukan'
      };
    }

    // Check if already approved/rejected
    if (approvalRow[8] !== APPROVAL_CONFIG.STATUS.PENDING) {
      return {
        status: 'error',
        message: 'Approval sudah di-' + approvalRow[8].toLowerCase()
      };
    }

    const changedBy = approvalRow[5];
    const changedByName = approvalRow[6];

    // Update approval status
    const reviewTimestamp = new Date();
    pendingSheet.getRange(rowIndex, 9).setValue(APPROVAL_CONFIG.STATUS.REJECTED); // Status
    pendingSheet.getRange(rowIndex, 10).setValue(adminEmail); // Reviewed_By
    pendingSheet.getRange(rowIndex, 11).setValue(reviewTimestamp); // Review_Timestamp
    pendingSheet.getRange(rowIndex, 12).setValue(reason || 'No reason provided'); // Rejection_Reason

    // Send rejection notification email
    if (APPROVAL_CONFIG.EMAIL_NOTIFICATIONS_ENABLED) {
      sendRejectionNotification(approvalId, changedBy, changedByName, adminEmail, reason);
    }

    Logger.log('✅ Change rejected successfully');

    return {
      status: 'success',
      message: 'Perubahan berhasil direject'
    };

  } catch (error) {
    Logger.log('❌ Error rejecting change: ' + error.message);
    return {
      status: 'error',
      message: 'Gagal reject change: ' + error.message
    };
  }
}

/**
 * Approve all pending changes
 * POST /exec?action=approveAll
 */
function approveAll(adminEmail) {
  try {
    Logger.log('✅ Approving all pending changes...');

    const pendingApprovals = getPendingApprovals(APPROVAL_CONFIG.STATUS.PENDING);

    if (pendingApprovals.status !== 'success') {
      return pendingApprovals;
    }

    const approvals = pendingApprovals.data;
    const results = {
      total: approvals.length,
      approved: 0,
      failed: 0,
      errors: []
    };

    for (const approval of approvals) {
      const result = approveChange(approval.approvalId, adminEmail);

      if (result.status === 'success') {
        results.approved++;
      } else {
        results.failed++;
        results.errors.push({
          approvalId: approval.approvalId,
          error: result.message
        });
      }
    }

    Logger.log(`✅ Approve all completed: ${results.approved}/${results.total} approved`);

    return {
      status: 'success',
      message: `${results.approved} dari ${results.total} perubahan berhasil diapprove`,
      results: results
    };

  } catch (error) {
    Logger.log('❌ Error in approve all: ' + error.message);
    return {
      status: 'error',
      message: 'Gagal approve all: ' + error.message
    };
  }
}

// ========================================
// 🔄 4. DATA UPDATE FUNCTIONS
// ========================================

/**
 * Update client data in the main sheet
 */
function updateClientData(sheetName, rowId, newData) {
  try {
    Logger.log('🔄 Updating client data in sheet: ' + sheetName);

    const spreadsheet = getClientDataSpreadsheet();
    const sheet = spreadsheet.getSheetByName(sheetName);

    if (!sheet) {
      return {
        status: 'error',
        message: 'Sheet tidak ditemukan: ' + sheetName
      };
    }

    const data = sheet.getDataRange().getValues();
    let targetRowIndex = -1;

    // Find the row by ID (assuming column A or E contains the ID)
    for (let i = 1; i < data.length; i++) {
      const accNo = data[i][4]; // Column E: ACC_NO

      if (accNo && accNo.toString() === rowId.toString()) {
        targetRowIndex = i + 1; // Sheet rows are 1-indexed
        break;
      }
    }

    if (targetRowIndex === -1) {
      return {
        status: 'error',
        message: 'Data dengan ID ' + rowId + ' tidak ditemukan'
      };
    }

    // Update each changed column
    for (const [columnLetter, value] of Object.entries(newData)) {
      const columnIndex = columnLetter.charCodeAt(0) - 64; // A=1, B=2, etc.
      sheet.getRange(targetRowIndex, columnIndex).setValue(value);
    }

    Logger.log('✅ Client data updated successfully');

    return {
      status: 'success',
      message: 'Data berhasil diupdate'
    };

  } catch (error) {
    Logger.log('❌ Error updating client data: ' + error.message);
    return {
      status: 'error',
      message: 'Gagal update data: ' + error.message
    };
  }
}

// ========================================
// 📜 5. HISTORY LOGGING FUNCTIONS
// ========================================

/**
 * Log changes to history sheet
 */
function logToHistory(historyData) {
  try {
    Logger.log('📜 Logging to history...');

    const spreadsheet = getClientDataSpreadsheet();
    let historySheet = spreadsheet.getSheetByName(APPROVAL_CONFIG.EDIT_HISTORY_SHEET);

    // Create sheet if doesn't exist
    if (!historySheet) {
      Logger.log('Creating Edit_History sheet...');
      historySheet = createEditHistorySheet(spreadsheet);
    }

    const timestamp = new Date();
    const historyId = 'HIS-' + timestamp.getTime();

    const rowData = [
      historyId,                                   // A: History_ID
      historyData.rowId || '',                     // B: Row_ID
      historyData.action || 'Update',              // C: Action
      JSON.stringify(historyData.oldData || {}),   // D: Old_Data
      JSON.stringify(historyData.newData || {}),   // E: New_Data
      historyData.changedBy || '',                 // F: Changed_By
      historyData.approvedBy || '',                // G: Approved_By
      timestamp,                                   // H: Timestamp
      historyData.sourceSheet || 'Client Aktif'    // I: Source_Sheet
    ];

    historySheet.appendRow(rowData);

    Logger.log('✅ History logged: ' + historyId);

  } catch (error) {
    Logger.log('❌ Error logging to history: ' + error.message);
  }
}

// ========================================
// 📧 6. EMAIL NOTIFICATION FUNCTIONS
// ========================================

/**
 * Send new approval request notification to admin
 */
function sendNewApprovalNotification(approvalId, approvalData) {
  try {
    Logger.log('📧 Sending new approval notification...');

    // Get admin emails
    const adminEmails = getAdminEmails();

    if (adminEmails.length === 0) {
      Logger.log('⚠️ No admin emails found');
      return;
    }

    const subject = '[Action Required] New Data Change Request - ' + approvalId;

    const changedFields = approvalData.changedColumns.map(col => {
      const oldValue = approvalData.originalData[col] || '-';
      const newValue = approvalData.newData[col] || '-';
      return `• ${col}: "${oldValue}" → "${newValue}"`;
    }).join('\n');

    const body = `
Hi Admin,

A new data change request has been submitted and requires your approval.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 APPROVAL DETAILS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Approval ID: ${approvalId}
Changed by: ${approvalData.changedByName} (${approvalData.changedBy})
Date: ${formatDateTime(new Date())}
Row ID: ${approvalData.rowId}
Source Sheet: ${approvalData.sourceSheet || 'Client Aktif'}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔄 CHANGES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

${changedFields}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Please review and approve/reject this request.

Best regards,
${APPROVAL_CONFIG.EMAIL_FROM_NAME}
    `.trim();

    adminEmails.forEach(email => {
      MailApp.sendEmail({
        to: email,
        subject: subject,
        body: body
      });
    });

    Logger.log('✅ Notification sent to ' + adminEmails.length + ' admin(s)');

  } catch (error) {
    Logger.log('❌ Error sending new approval notification: ' + error.message);
  }
}

/**
 * Send approval/rejection notification to submitter
 */
function sendApprovalNotification(approvalId, submitterEmail, submitterName, adminEmail, isApproved) {
  try {
    Logger.log('📧 Sending approval notification...');

    const status = isApproved ? 'APPROVED' : 'REJECTED';
    const subject = `[${status}] Your Data Change Request - ${approvalId}`;

    const body = `
Hi ${submitterName || 'User'},

Your data change request has been ${status} by ${adminEmail}.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 APPROVAL DETAILS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Request ID: ${approvalId}
${isApproved ? 'Approved' : 'Reviewed'} by: ${adminEmail}
${isApproved ? 'Approved' : 'Review'} Date: ${formatDateTime(new Date())}

${isApproved ? 'Your changes have been applied to the main data.' : 'Please contact the admin if you have questions.'}

Best regards,
${APPROVAL_CONFIG.EMAIL_FROM_NAME}
    `.trim();

    MailApp.sendEmail({
      to: submitterEmail,
      subject: subject,
      body: body
    });

    Logger.log('✅ Approval notification sent to ' + submitterEmail);

  } catch (error) {
    Logger.log('❌ Error sending approval notification: ' + error.message);
  }
}

/**
 * Send rejection notification with reason
 */
function sendRejectionNotification(approvalId, submitterEmail, submitterName, adminEmail, reason) {
  try {
    Logger.log('📧 Sending rejection notification...');

    const subject = `[REJECTED] Your Data Change Request - ${approvalId}`;

    const body = `
Hi ${submitterName || 'User'},

Your data change request has been REJECTED by ${adminEmail}.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📋 REJECTION DETAILS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Request ID: ${approvalId}
Rejected by: ${adminEmail}
Rejection Date: ${formatDateTime(new Date())}

Reason: ${reason || 'No reason provided'}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Please contact the admin if you have questions.

Best regards,
${APPROVAL_CONFIG.EMAIL_FROM_NAME}
    `.trim();

    MailApp.sendEmail({
      to: submitterEmail,
      subject: subject,
      body: body
    });

    Logger.log('✅ Rejection notification sent to ' + submitterEmail);

  } catch (error) {
    Logger.log('❌ Error sending rejection notification: ' + error.message);
  }
}

/**
 * Get list of admin emails
 */
function getAdminEmails() {
  try {
    const spreadsheet = getClientDataSpreadsheet();
    const adminSheet = spreadsheet.getSheetByName(APPROVAL_CONFIG.ADMIN_USERS_SHEET);

    if (!adminSheet) {
      Logger.log('⚠️ Admin_Users sheet not found');
      return [];
    }

    const data = adminSheet.getDataRange().getValues();
    const emails = [];

    for (let i = 1; i < data.length; i++) {
      const email = data[i][0];
      const role = data[i][2];

      if (email && role === 'Admin') {
        emails.push(email);
      }
    }

    return emails;

  } catch (error) {
    Logger.log('❌ Error getting admin emails: ' + error.message);
    return [];
  }
}

// ========================================
// 🏗️ 7. SHEET CREATION FUNCTIONS
// ========================================

/**
 * Create Pending_Approvals sheet with proper structure
 */
function createPendingApprovalsSheet(spreadsheet) {
  const sheet = spreadsheet.insertSheet(APPROVAL_CONFIG.PENDING_APPROVALS_SHEET);

  // Set headers
  const headers = [
    'ID', 'Row_ID', 'Original_Data', 'New_Data', 'Changed_Columns',
    'Changed_By', 'Changed_By_Name', 'Timestamp', 'Status',
    'Reviewed_By', 'Review_Timestamp', 'Rejection_Reason', 'Source_Sheet'
  ];

  sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
  sheet.getRange(1, 1, 1, headers.length).setFontWeight('bold');
  sheet.getRange(1, 1, 1, headers.length).setBackground('#4285f4');
  sheet.getRange(1, 1, 1, headers.length).setFontColor('#ffffff');

  // Set column widths
  sheet.setColumnWidth(1, 150);  // ID
  sheet.setColumnWidth(2, 120);  // Row_ID
  sheet.setColumnWidth(3, 300);  // Original_Data
  sheet.setColumnWidth(4, 300);  // New_Data
  sheet.setColumnWidth(5, 200);  // Changed_Columns
  sheet.setColumnWidth(6, 200);  // Changed_By
  sheet.setColumnWidth(7, 150);  // Changed_By_Name
  sheet.setColumnWidth(8, 150);  // Timestamp
  sheet.setColumnWidth(9, 100);  // Status
  sheet.setColumnWidth(10, 200); // Reviewed_By
  sheet.setColumnWidth(11, 150); // Review_Timestamp
  sheet.setColumnWidth(12, 250); // Rejection_Reason
  sheet.setColumnWidth(13, 150); // Source_Sheet

  sheet.setFrozenRows(1);

  Logger.log('✅ Pending_Approvals sheet created');

  return sheet;
}

/**
 * Create Edit_History sheet with proper structure
 */
function createEditHistorySheet(spreadsheet) {
  const sheet = spreadsheet.insertSheet(APPROVAL_CONFIG.EDIT_HISTORY_SHEET);

  // Set headers
  const headers = [
    'History_ID', 'Row_ID', 'Action', 'Old_Data', 'New_Data',
    'Changed_By', 'Approved_By', 'Timestamp', 'Source_Sheet'
  ];

  sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
  sheet.getRange(1, 1, 1, headers.length).setFontWeight('bold');
  sheet.getRange(1, 1, 1, headers.length).setBackground('#34a853');
  sheet.getRange(1, 1, 1, headers.length).setFontColor('#ffffff');

  // Set column widths
  sheet.setColumnWidth(1, 150);  // History_ID
  sheet.setColumnWidth(2, 120);  // Row_ID
  sheet.setColumnWidth(3, 100);  // Action
  sheet.setColumnWidth(4, 300);  // Old_Data
  sheet.setColumnWidth(5, 300);  // New_Data
  sheet.setColumnWidth(6, 200);  // Changed_By
  sheet.setColumnWidth(7, 200);  // Approved_By
  sheet.setColumnWidth(8, 150);  // Timestamp
  sheet.setColumnWidth(9, 150);  // Source_Sheet

  sheet.setFrozenRows(1);

  Logger.log('✅ Edit_History sheet created');

  return sheet;
}

/**
 * Create Admin_Users sheet with proper structure
 */
function createAdminUsersSheet(spreadsheet) {
  const sheet = spreadsheet.insertSheet(APPROVAL_CONFIG.ADMIN_USERS_SHEET);

  // Set headers
  const headers = ['Email', 'Name', 'Role', 'Added_Date'];

  sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
  sheet.getRange(1, 1, 1, headers.length).setFontWeight('bold');
  sheet.getRange(1, 1, 1, headers.length).setBackground('#ea4335');
  sheet.getRange(1, 1, 1, headers.length).setFontColor('#ffffff');

  // Set column widths
  sheet.setColumnWidth(1, 250);  // Email
  sheet.setColumnWidth(2, 200);  // Name
  sheet.setColumnWidth(3, 100);  // Role
  sheet.setColumnWidth(4, 150);  // Added_Date

  sheet.setFrozenRows(1);

  Logger.log('✅ Admin_Users sheet created');

  return sheet;
}

// ========================================
// 🔌 8. EXTEND doPost TO HANDLE NEW ACTIONS
// ========================================

/**
 * Add this to your existing doPost function to handle approval actions
 *
 * Example integration:
 *
 * function doPost(e) {
 *   try {
 *     const postData = JSON.parse(e.postData.contents);
 *     const action = postData.action;
 *
 *     // ... existing actions ...
 *
 *     // ADD THESE NEW ACTIONS:
 *     else if (action === 'addPendingApproval') {
 *       const result = addPendingApproval(postData.approvalData);
 *       return ContentService.createTextOutput(JSON.stringify(result))
 *         .setMimeType(ContentService.MimeType.JSON);
 *     }
 *
 *     else if (action === 'getPendingApprovals') {
 *       const result = getPendingApprovals(postData.filterStatus);
 *       return ContentService.createTextOutput(JSON.stringify(result))
 *         .setMimeType(ContentService.MimeType.JSON);
 *     }
 *
 *     else if (action === 'approveChange') {
 *       const result = approveChange(postData.approvalId, postData.adminEmail);
 *       return ContentService.createTextOutput(JSON.stringify(result))
 *         .setMimeType(ContentService.MimeType.JSON);
 *     }
 *
 *     else if (action === 'rejectChange') {
 *       const result = rejectChange(postData.approvalId, postData.adminEmail, postData.reason);
 *       return ContentService.createTextOutput(JSON.stringify(result))
 *         .setMimeType(ContentService.MimeType.JSON);
 *     }
 *
 *     else if (action === 'approveAll') {
 *       const result = approveAll(postData.adminEmail);
 *       return ContentService.createTextOutput(JSON.stringify(result))
 *         .setMimeType(ContentService.MimeType.JSON);
 *     }
 *
 *     else if (action === 'isAdmin') {
 *       const result = { isAdmin: isAdmin(postData.email) };
 *       return ContentService.createTextOutput(JSON.stringify(result))
 *         .setMimeType(ContentService.MimeType.JSON);
 *     }
 *
 *     // ... rest of your code ...
 *
 *   } catch (error) {
 *     // error handling
 *   }
 * }
 */
