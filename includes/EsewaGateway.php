<?php
require_once __DIR__ . '/../config/config.php';

class EsewaGateway {

    private $merchantId;
    private $secretKey;
    private $testMode;
    private $apiUrl;
    private $statusUrl;

    public function __construct($testMode = true) {
        $this->testMode = $testMode;

        if ($this->testMode) {
            // TEST (UAT)
            $this->merchantId = 'EPAYTEST';
            $this->secretKey  = '8gBm/:&EnhH.1/q(';
            $this->apiUrl     = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
            $this->statusUrl  = 'https://rc.esewa.com.np/api/epay/transaction/status/';
        } else {
            // PRODUCTION
            $this->merchantId = 'YOUR_LIVE_MERCHANT_ID';
            $this->secretKey  = 'YOUR_LIVE_SECRET_KEY';
            $this->apiUrl     = 'https://epay.esewa.com.np/api/epay/main/v2/form';
            $this->statusUrl  = 'https://esewa.com.np/api/epay/transaction/status/';
        }
    }

    /* ===========================
       BASIC GETTERS
    ============================ */

    public function isTestMode() {
        return $this->testMode;
    }

    public function getMerchantId() {
        return $this->merchantId;
    }

    public function getApiUrl() {
        return $this->apiUrl;
    }

    /* ===========================
       SIGNATURE GENERATION
    ============================ */

    protected function generateSignature($totalAmount, $transactionUuid) {
        $message = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code={$this->merchantId}";
        $hash = hash_hmac('sha256', $message, $this->secretKey, true);
        return base64_encode($hash);
    }

    /* ===========================
       PAYMENT FORM GENERATION
    ============================ */

    public function generatePaymentForm($amount, $successUrl, $failureUrl) {

        $transactionUuid = uniqid('TXN-'); // alphanumeric + hyphen

        $taxAmount = 0;
        $productServiceCharge = 0;
        $productDeliveryCharge = 0;

        $totalAmount = $amount + $taxAmount + $productServiceCharge + $productDeliveryCharge;

        $signature = $this->generateSignature($totalAmount, $transactionUuid);

        $formData = [
            'amount' => $amount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $this->merchantId,
            'product_service_charge' => $productServiceCharge,
            'product_delivery_charge' => $productDeliveryCharge,
            'success_url' => $successUrl,
            'failure_url' => $failureUrl,
            'signed_field_names' => 'total_amount,transaction_uuid,product_code',
            'signature' => $signature
        ];

        return [
            'form_action' => $this->apiUrl,
            'form_data' => $formData,
            'transaction_uuid' => $transactionUuid,
            'signature' => $signature
        ];
    }

    /* ===========================
       RESPONSE SIGNATURE VERIFY
    ============================ */

    public function verifyResponseSignature(array $data) {

        if (!isset($data['signed_field_names'], $data['signature'])) {
            return false;
        }

        $fields = explode(',', $data['signed_field_names']);
        $parts = [];

        foreach ($fields as $field) {
            if (!isset($data[$field])) return false;
            $parts[] = $field . '=' . $data[$field];
        }

        $message = implode(',', $parts);

        $generated = base64_encode(
            hash_hmac('sha256', $message, $this->secretKey, true)
        );

        return hash_equals($generated, $data['signature']); // timing-safe
    }

    /* ===========================
       STATUS CHECK API (v2)
    ============================ */

    public function statusCheck($transactionUuid, $totalAmount) {

        $url = $this->statusUrl . '?' . http_build_query([
            'product_code' => $this->merchantId,
            'total_amount' => $totalAmount,
            'transaction_uuid' => $transactionUuid
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /* ===========================
       DATABASE METHODS
    ============================ */

    public function createPaymentRecord($studentId, $courseId, $amount, $transactionUuid, $status = 'pending') {

        $conn = connectDB();

        $stmt = $conn->prepare("
            INSERT INTO payments 
            (student_id, course_id, amount, transaction_uuid, payment_method, status, created_at) 
            VALUES (?, ?, ?, ?, 'esewa', ?, NOW())
        ");

        $stmt->bind_param("iidss", $studentId, $courseId, $amount, $transactionUuid, $status);

        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            $conn->close();
            return ['success' => true, 'payment_id' => $id];
        }

        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'error' => $err];
    }

    public function updatePaymentStatus($transactionUuid, $status, $refId = null, $gatewayResponse = []) {

        $conn = connectDB();

        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = ?, ref_id = ?, gateway_response = ?, updated_at = NOW()
            WHERE transaction_uuid = ?
        ");

        $responseJson = json_encode($gatewayResponse);
        $stmt->bind_param("ssss", $status, $refId, $responseJson, $transactionUuid);

        if ($stmt->execute()) {
            $rows = $stmt->affected_rows;
            $stmt->close();
            $conn->close();
            return ['success' => true, 'affected_rows' => $rows];
        }

        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'error' => $err];
    }

    public function getPaymentByTransactionUuid($transactionUuid) {

        $conn = connectDB();

        $stmt = $conn->prepare("
            SELECT p.*, c.title AS course_title, u.full_name AS student_name 
            FROM payments p
            LEFT JOIN courses_new c ON p.course_id = c.id
            LEFT JOIN users_new u ON p.student_id = u.id
            WHERE p.transaction_uuid = ?
        ");

        $stmt->bind_param("s", $transactionUuid);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();

        $stmt->close();
        $conn->close();

        return $payment;
    }

    /* ===========================
       FRONTEND CONFIG
    ============================ */

    public function getConfig() {
        return [
            'test_mode' => $this->testMode,
            'merchant_id' => $this->merchantId,
            'api_url' => $this->apiUrl,
            'status_url' => $this->statusUrl,
            'currency' => 'NPR',
            'min_amount' => 1,
            'max_amount' => 100000
        ];
    }
}
?>
