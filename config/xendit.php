<?php
/**
 * Xendit Configuration
 * Koperasi Syariah Online
 */

// Xendit API Configuration
define('XENDIT_API_KEY', 'xnd_development_XXXXXX'); // Ganti dengan API Key Xendit Anda
define('XENDIT_API_URL', 'https://api.xendit.com');

// Xendit Invoice Settings
define('XENDIT_SUCCESS_REDIRECT_URL', 'http://yourdomain.com/customer/payment-success.php'); // Ganti dengan domain Anda
define('XENDIT_FAILURE_REDIRECT_URL', 'http://yourdomain.com/customer/payment-failed.php');  // Ganti dengan domain Anda

/**
 * Create Xendit Invoice (Payment Link)
 */
function createXenditInvoice($externalId, $amount, $payerEmail, $description) {
    $data = [
        'external_id' => $externalId,
        'amount' => $amount,
        'payer_email' => $payerEmail,
        'description' => $description,
        'success_redirect_url' => XENDIT_SUCCESS_REDIRECT_URL,
        'failure_redirect_url' => XENDIT_FAILURE_REDIRECT_URL,
        'currency' => 'IDR',
        'invoice_duration' => 86400, // 24 hours
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, XENDIT_API_URL . '/v2/invoices');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_USERPWD, XENDIT_API_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 || $httpCode == 201) {
        return json_decode($response, true);
    } else {
        return [
            'error' => true,
            'message' => 'Failed to create invoice',
            'response' => json_decode($response, true)
        ];
    }
}

/**
 * Check Xendit Invoice Status
 */
function checkXenditInvoiceStatus($invoiceId) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, XENDIT_API_URL . '/v2/invoices/' . $invoiceId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, XENDIT_API_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        return json_decode($response, true);
    } else {
        return [
            'error' => true,
            'message' => 'Failed to check invoice status',
            'response' => json_decode($response, true)
        ];
    }
}
?>
