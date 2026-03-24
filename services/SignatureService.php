<?php
require_once __DIR__ . '/../config/config.php';

/**
 * SignatureService - Handles HMAC SHA256 signature generation and verification for eSewa
 * 
 * This service provides secure signature generation and verification for eSewa payment gateway
 * following the eSewa ePay API specifications.
 */
class SignatureService {
    private $secretKey;
    private $productCode;
    private $testMode;
    
    public function __construct() {
        $this->loadConfiguration();
    }
    
    /**
     * Load eSewa configuration from database or config
     */
    private function loadConfiguration() {
        try {
            $conn = connectDB();
            
            // Get settings from database
            $stmt = $conn->prepare("SELECT setting_key, setting_value FROM payment_settings WHERE setting_key IN ('esewa_secret_key', 'esewa_product_code', 'esewa_test_mode')");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $settings = [];
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            $this->secretKey = $settings['esewa_secret_key'] ?? '8gBm/:&EnhH.1/q(';
            $this->productCode = $settings['esewa_product_code'] ?? 'EPAYTEST';
            $this->testMode = ($settings['esewa_test_mode'] ?? 'true') === 'true';
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            // Fallback to default values
            $this->secretKey = '8gBm/:&EnhH.1/q(';
            $this->productCode = 'EPAYTEST';
            $this->testMode = true;
            
            error_log("SignatureService: Failed to load configuration - " . $e->getMessage());
        }
    }
    
    /**
     * Generate eSewa signature
     * 
     * @param array $data Data containing total_amount and transaction_uuid
     * @return string Base64 encoded signature
     */
    public function generateSignature($data) {
        try {
            // Validate required fields
            if (!isset($data['total_amount']) || !isset($data['transaction_uuid'])) {
                throw new InvalidArgumentException('Missing required fields: total_amount, transaction_uuid');
            }
            
            // Generate signature using exact eSewa format
            // Signed fields must be in exact order: total_amount,transaction_uuid,product_code
            $message = sprintf(
                "total_amount=%s,transaction_uuid=%s,product_code=%s",
                $data['total_amount'],
                $data['transaction_uuid'],
                $this->productCode
            );
            
            // HMAC-SHA256 → Base64
            $hash = hash_hmac('sha256', $message, $this->secretKey, true);
            $signature = base64_encode($hash);
            
            // Log signature generation for debugging
            $this->logSignatureActivity('generate', $message, $signature, $data);
            
            return $signature;
            
        } catch (Exception $e) {
            error_log("SignatureService: Signature generation failed - " . $e->getMessage());
            throw new Exception("Failed to generate signature: " . $e->getMessage());
        }
    }
    
    /**
     * Verify eSewa response signature
     * 
     * @param array $data Response data from eSewa
     * @param string $receivedSignature Base64 signature received from eSewa
     * @return bool True if signature is valid
     */
    public function verifySignature($data, $receivedSignature) {
        try {
            // Validate required fields
            if (!isset($data['total_amount']) || !isset($data['transaction_uuid'])) {
                throw new InvalidArgumentException('Missing required fields for verification');
            }
            
            // Check if this is a response from eSewa (has different signed_field_names)
            $signedFieldNames = $data['signed_field_names'] ?? 'total_amount,transaction_uuid,product_code';
            
            if ($signedFieldNames === 'transaction_code,status,total_amount,transaction_uuid,product_code,signed_field_names') {
                // This is eSewa's response format - use their signed_field_names
                $signatureString = sprintf(
                    "transaction_code=%s,status=%s,total_amount=%s,transaction_uuid=%s,product_code=%s,signed_field_names=%s",
                    $data['transaction_code'] ?? '',
                    $data['status'] ?? '',
                    $data['total_amount'],
                    $data['transaction_uuid'],
                    $this->productCode,
                    $signedFieldNames
                );
            } else {
                // This is our request format
                $signatureString = sprintf(
                    "total_amount=%s,transaction_uuid=%s,product_code=%s",
                    $data['total_amount'],
                    $data['transaction_uuid'],
                    $this->productCode
                );
            }
            
            // Generate HMAC SHA256 hash
            $hmacHash = hash_hmac('sha256', $signatureString, $this->secretKey, true);
            
            // Encode to Base64
            $expectedSignature = base64_encode($hmacHash);
            
            // Compare signatures securely
            $isValid = hash_equals($expectedSignature, $receivedSignature);
            
            // Log verification attempt
            $this->logSignatureActivity('verify', $signatureString, $receivedSignature, [
                'expected' => $expectedSignature,
                'valid' => $isValid,
                'signed_field_names' => $signedFieldNames
            ]);
            
            return $isValid;
            
        } catch (Exception $e) {
            error_log("SignatureService: Signature verification failed - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate signed field names string for eSewa
     * 
     * @return string Comma-separated field names
     */
    public function getSignedFieldNames() {
        return "total_amount,transaction_uuid,product_code";
    }
    
    /**
     * Get product code
     * 
     * @return string Product code
     */
    public function getProductCode() {
        return $this->productCode;
    }
    
    /**
     * Get test mode status
     * 
     * @return bool True if test mode is enabled
     */
    public function isTestMode() {
        return $this->testMode;
    }
    
    /**
     * Get eSewa payment URL based on test mode
     * 
     * @return string eSewa payment URL
     */
    public function getPaymentUrl() {
        return $this->testMode 
            ? 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'
            : 'https://esewa.com.np/api/epay/main/v2/form';
    }
    
    /**
     * Get eSewa status verification URL
     * 
     * @return string Status verification URL
     */
    public function getVerificationUrl() {
        return $this->testMode 
            ? 'https://rc-epay.esewa.com.np/api/epay/transaction/status/'
            : 'https://esewa.com.np/api/epay/transaction/status/';
    }
    
    /**
     * Generate unique transaction UUID
     * 
     * @param int $userId User ID for uniqueness
     * @param int $courseId Course ID for uniqueness
     * @return string Unique transaction UUID
     */
    public function generateTransactionUUID($userId = null, $courseId = null) {
        $uuid = uniqid() . '-' . time();
        
        if ($userId) {
            $uuid .= '-U' . $userId;
        }
        
        if ($courseId) {
            $uuid .= '-C' . $courseId;
        }
        
        return $uuid;
    }
    
    /**
     * Validate payment response data
     * 
     * @param array $data Response data to validate
     * @return array Validation result with errors if any
     */
    public function validateResponseData($data) {
        $errors = [];
        
        // Check required fields
        $requiredFields = ['transaction_uuid', 'total_amount', 'status'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }
        
        // Validate amount format
        if (isset($data['total_amount']) && !is_numeric($data['total_amount'])) {
            $errors[] = "Invalid amount format";
        }
        
        // Validate transaction UUID format
        if (isset($data['transaction_uuid']) && strlen($data['transaction_uuid']) < 10) {
            $errors[] = "Invalid transaction UUID format";
        }
        
        // Validate status
        if (isset($data['status'])) {
            $validStatuses = ['COMPLETE', 'PENDING', 'FAILED', 'REFUNDED'];
            if (!in_array(strtoupper($data['status']), $validStatuses)) {
                $errors[] = "Invalid payment status";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Log signature activities for debugging and audit
     * 
     * @param string $action Action performed (generate/verify)
     * @param string $input Input data
     * @param string $signature Generated or received signature
     * @param array $additional Additional data to log
     */
    private function logSignatureActivity($action, $input, $signature, $additional = []) {
        try {
            $logData = [
                'action' => $action,
                'timestamp' => date('Y-m-d H:i:s'),
                'input' => $input,
                'signature' => substr($signature, 0, 20) . '...', // Log partial signature for security
                'additional' => $additional,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ];
            
            error_log("SignatureService: {$action} - " . json_encode($logData));
            
        } catch (Exception $e) {
            error_log("SignatureService: Logging failed - " . $e->getMessage());
        }
    }
    
    /**
     * Create payment form data array for eSewa
     * 
     * @param array $paymentData Payment information
     * @return array Form data ready for eSewa submission
     */
    public function createPaymentFormData($paymentData) {
        try {
            $transactionUuid = $paymentData['transaction_uuid'] ?? $this->generateTransactionUUID(
                $paymentData['user_id'] ?? null,
                $paymentData['course_id'] ?? null
            );
            
            $formData = [
                'amount' => $paymentData['amount'],
                'tax_amount' => $paymentData['tax_amount'] ?? 0,
                'total_amount' => $paymentData['amount'],
                'transaction_uuid' => $transactionUuid,
                'product_code' => $this->productCode,
                'product_service_charge' => $paymentData['service_charge'] ?? 0,
                'product_delivery_charge' => $paymentData['delivery_charge'] ?? 0,
                'success_url' => BASE_URL . 'payments/esewa_success.php',
                'failure_url' => BASE_URL . 'payments/esewa_failure.php',
                'signed_field_names' => $this->getSignedFieldNames(),
                'signature' => $this->generateSignature([
                    'total_amount' => $paymentData['amount'],
                    'transaction_uuid' => $transactionUuid
                ])
            ];
            
            return $formData;
            
        } catch (Exception $e) {
            error_log("SignatureService: Form data creation failed - " . $e->getMessage());
            throw new Exception("Failed to create payment form data: " . $e->getMessage());
        }
    }
    
    /**
     * Get configuration details (for debugging)
     * 
     * @return array Configuration details (excluding sensitive data)
     */
    public function getConfiguration() {
        return [
            'product_code' => $this->productCode,
            'test_mode' => $this->testMode,
            'payment_url' => $this->getPaymentUrl(),
            'verification_url' => $this->getVerificationUrl(),
            'signed_field_names' => $this->getSignedFieldNames()
        ];
    }
}
?>
