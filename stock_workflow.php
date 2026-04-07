<?php
require_once 'brevo_config.php';

function ensure_stock_workflow_tables(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS stock_receipts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            quantity_received INT NOT NULL,
            note TEXT NULL,
            received_by VARCHAR(100) NOT NULL,
            received_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (product_id)
        )"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS supplier_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            supplier_name VARCHAR(255) NOT NULL,
            supplier_email VARCHAR(255) NOT NULL,
            quantity_requested INT NOT NULL,
            note TEXT NULL,
            ordered_by VARCHAR(100) NOT NULL,
            brevo_message_id VARCHAR(255) NULL,
            delivery_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            brevo_status_code INT NULL,
            brevo_error TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (product_id)
        )"
    );

    $existingColumns = [];
    $columnsResult = $conn->query("SHOW COLUMNS FROM supplier_orders");
    if ($columnsResult) {
        while ($column = $columnsResult->fetch_assoc()) {
            $existingColumns[] = $column['Field'];
        }
    }

    if (!in_array('brevo_status_code', $existingColumns, true)) {
        $conn->query("ALTER TABLE supplier_orders ADD COLUMN brevo_status_code INT NULL AFTER delivery_status");
    }

    if (!in_array('brevo_error', $existingColumns, true)) {
        $conn->query("ALTER TABLE supplier_orders ADD COLUMN brevo_error TEXT NULL AFTER brevo_status_code");
    }
}

function brevo_sender_ready(): bool
{
    return defined('BREVO_SENDER_EMAIL')
        && BREVO_SENDER_EMAIL !== ''
        && stripos(BREVO_SENDER_EMAIL, 'example.com') === false;
}

function brevo_owner_copy_ready(): bool
{
    return defined('BREVO_OWNER_COPY_EMAIL')
        && BREVO_OWNER_COPY_EMAIL !== ''
        && filter_var(BREVO_OWNER_COPY_EMAIL, FILTER_VALIDATE_EMAIL) !== false;
}

function send_brevo_email(string $toEmail, string $toName, string $subject, string $htmlContent, string $textContent = '', bool $sendOwnerCopy = false): array
{
    if (!defined('BREVO_API_KEY') || BREVO_API_KEY === '') {
        return ['success' => false, 'error' => 'Brevo API key is missing.', 'status_code' => 0, 'response_body' => null];
    }

    if (!brevo_sender_ready()) {
        return ['success' => false, 'error' => 'Set a verified Brevo sender email in brevo_config.php before sending supplier emails.', 'status_code' => 0, 'response_body' => null];
    }

    $payload = [
        'sender' => [
            'name' => BREVO_SENDER_NAME,
            'email' => BREVO_SENDER_EMAIL,
        ],
        'to' => [[
            'email' => $toEmail,
            'name' => $toName,
        ]],
        'subject' => $subject,
        'htmlContent' => $htmlContent,
    ];

    if ($sendOwnerCopy && brevo_owner_copy_ready()) {
        $payload['cc'] = [[
            'email' => BREVO_OWNER_COPY_EMAIL,
            'name' => defined('BREVO_OWNER_COPY_NAME') ? BREVO_OWNER_COPY_NAME : 'Owner Copy',
        ]];
    }

    if ($textContent !== '') {
        $payload['textContent'] = $textContent;
    }

    $jsonPayload = json_encode($payload);

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'api-key: ' . BREVO_API_KEY,
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => 'Brevo request failed: ' . $curlError, 'status_code' => $statusCode, 'response_body' => null];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'accept: application/json',
                    'api-key: ' . BREVO_API_KEY,
                    'content-type: application/json',
                ]),
                'content' => $jsonPayload,
                'timeout' => 20,
            ],
        ]);

        $response = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $context);
        $statusCode = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
            $statusCode = (int) $matches[1];
        }

        if ($response === false) {
            return ['success' => false, 'error' => 'Brevo request failed. Check network access and sender configuration.', 'status_code' => $statusCode, 'response_body' => null];
        }
    }

    $decoded = json_decode($response, true);

    if ($statusCode >= 200 && $statusCode < 300) {
        return [
            'success' => true,
            'message_id' => $decoded['messageId'] ?? null,
            'status_code' => $statusCode,
            'response_body' => $response,
        ];
    }

    $errorMessage = $decoded['message'] ?? 'Brevo email sending failed.';
    return ['success' => false, 'error' => $errorMessage, 'status_code' => $statusCode, 'response_body' => $response];
}
?>
