<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/SignatureService.php';

/**
 * Payment Service Class
 * 
 * Handles payment processing for eSewa integration
 */
class PaymentService {
    private $db;
    private $signatureService;
    
    public function __construct() {
        $this->db = new Database();
        $this->signatureService = new SignatureService();
    }
    
    /**
     * Create a new payment record (Official eSewa Implementation)
     * 
     * @param array $paymentData Payment information
     * @return array Payment creation result
     */
    public function createPayment($paymentData) {
        try {
            $conn = $this->db->getConnection();
            
            // Generate transaction UUID using official format
            $transactionUuid = uniqid('TXN-');
            
            // Calculate amounts (using official eSewa format)
            $amount = $paymentData['amount'];
            $taxAmount = $paymentData['tax_amount'] ?? 0;
            $productServiceCharge = $paymentData['product_service_charge'] ?? 0;
            $productDeliveryCharge = $paymentData['product_delivery_charge'] ?? 0;
            $totalAmount = $amount + $taxAmount + $productServiceCharge + $productDeliveryCharge;
            
            // Generate signature using official eSewa format
            $signatureData = [
                'total_amount' => $totalAmount,
                'transaction_uuid' => $transactionUuid,
                'product_code' => 'EPAYTEST'
            ];
            
            // Debug: Log the signature data
            error_log("eSewa Signature Data: " . json_encode($signatureData));
            
            $signature = $this->signatureService->generateSignature($signatureData);
            
            // Debug: Log the generated signature
            error_log("eSewa Generated Signature: " . $signature);
            
            // Create payment record
            $query = "INSERT INTO payments (
                user_id, course_id, amount, currency, payment_method, 
                transaction_uuid, status, gateway_response, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($query);
            
            // Assign variables to pass by reference
            $userId = $paymentData['user_id'];
            $courseId = $paymentData['course_id'];
            $amount = $amount;
            $currency = $paymentData['currency'] ?? 'NPR';
            $paymentMethod = $paymentData['payment_method'];
            $transactionUuid = $transactionUuid;
            $status = 'pending';
            $gatewayResponse = json_encode(['signature' => $signature]);
            
            $stmt->bind_param(
                "iidsssss",
                $userId,
                $courseId,
                $amount,
                $currency,
                $paymentMethod,
                $transactionUuid,
                $status,
                $gatewayResponse
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create payment record");
            }
            
            $paymentId = $conn->insert_id;
            
            // Create eSewa form data (official format)
            $formData = [
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'transaction_uuid' => $transactionUuid,
                'product_code' => 'EPAYTEST',
                'product_service_charge' => $productServiceCharge,
                'product_delivery_charge' => $productDeliveryCharge,
                'success_url' => BASE_URL . 'payments/esewa_success.php',
                'failure_url' => BASE_URL . 'payments/esewa_failure.php',
                'signed_field_names' => 'total_amount,transaction_uuid,product_code',
                'signature' => $signature
            ];
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'transaction_uuid' => $transactionUuid,
                'payment_form' => [
                    'form_action' => 'https://rc-epay.esewa.com.np/api/epay/main/v2/form',
                    'form_data' => $formData
                ]
            ];
            
        } catch (Exception $e) {
            error_log("PaymentService: Payment creation failed - " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check eSewa payment status (Official API)
     * 
     * @param string $transactionUuid Transaction UUID
     * @param float $totalAmount Total amount
     * @return array Status check result
     */
    public function checkEsewaStatus($transactionUuid, $totalAmount) {
        try {
            $url = "https://rc-epay.esewa.com.np/api/epay/transaction/status/?" . http_build_query([
                'product_code' => 'EPAYTEST',
                'total_amount' => $totalAmount,
                'transaction_uuid' => $transactionUuid
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("cURL error: " . $error);
            }

            if ($httpCode !== 200) {
                throw new Exception("HTTP error: " . $httpCode);
            }

            $data = json_decode($response, true);
            
            if (!$data) {
                throw new Exception("Invalid JSON response");
            }

            return [
                'success' => true,
                'data' => $data,
                'status' => $data['status'] ?? 'unknown'
            ];
            
        } catch (Exception $e) {
            error_log("PaymentService: eSewa status check failed - " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process eSewa success response
     * 
     * @param array $responseData Response data from eSewa
     * @return array Processing result
     */
    public function processEsewaSuccess($responseData) {
        try {
            $conn = $this->db->getConnection();
            
            // Update payment status
            $query = "UPDATE payments SET 
                status = 'completed',
                gateway_response = ?,
                updated_at = NOW()
                WHERE transaction_uuid = ? AND status = 'pending'";
            
            $stmt = $conn->prepare($query);
            $gatewayResponse = json_encode($responseData);
            $transactionUuid = $responseData['transaction_uuid'];
            $stmt->bind_param("ss", $gatewayResponse, $transactionUuid);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update payment status");
            }
            
            // Get payment details
            $query = "SELECT * FROM payments WHERE transaction_uuid = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $responseData['transaction_uuid']);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();
            
            if (!$payment) {
                throw new Exception("Payment not found");
            }
            
            return [
                'success' => true,
                'payment' => $payment
            ];
            
        } catch (Exception $e) {
            error_log("PaymentService: eSewa success processing failed - " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get payment by transaction UUID
     * 
     * @param string $transactionUuid Transaction UUID
     * @return array|null Payment record
     */
    public function getPaymentByTransactionUuid($transactionUuid) {
        try {
            $conn = $this->db->getConnection();
            
            $query = "SELECT * FROM payments WHERE transaction_uuid = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $transactionUuid);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("PaymentService: Failed to get payment - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get pending payment for user and course
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array|null Payment record
     */
    public function getPendingPayment($userId, $courseId) {
        try {
            $conn = $this->db->getConnection();
            
            $query = "SELECT * FROM payments 
                     WHERE user_id = ? AND course_id = ? AND status = 'pending' 
                     ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $userId, $courseId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("PaymentService: Failed to get pending payment - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify eSewa payment by checking status with eSewa API
     * 
     * @param array $responseData Response data from eSewa
     * @return array Verification result
     */
    public function verifyEsewaPayment($responseData) {
        try {
            $transactionUuid = $responseData['transaction_uuid'] ?? '';
            $totalAmount = $responseData['total_amount'] ?? '';
            
            if (empty($transactionUuid) || empty($totalAmount)) {
                return ['success' => false, 'error' => 'Missing required parameters'];
            }
            
            // Check payment status using eSewa API
            $statusCheck = $this->checkEsewaStatus($transactionUuid, $totalAmount);
            
            if (!$statusCheck['success']) {
                return ['success' => false, 'error' => $statusCheck['error']];
            }
            
            // Verify the status is "complete"
            if ($statusCheck['status'] !== 'Complete') {
                return ['success' => false, 'error' => 'Payment not completed. Status: ' . $statusCheck['status']];
            }
            
            // Update payment to completed
            $updateResult = $this->processEsewaSuccess($responseData);
            
            return $updateResult;
            
        } catch (Exception $e) {
            error_log("PaymentService: eSewa verification failed - " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update payment status
     * 
     * @param int $paymentId Payment ID
     * @param string $status New status
     * @param string $failureReason Failure reason if applicable
     * @param array $additionalData Additional data to update
     * @return bool Success status
     */
    public function updatePaymentStatus($paymentId, $status, $failureReason = '', $additionalData = []) {
        try {
            $conn = $this->db->getConnection();
            
            $query = "UPDATE payments SET status = ?, failure_reason = ?, updated_at = NOW()";
            $params = [$status, $failureReason];
            $types = "ss";
            
            // Add additional fields if provided
            if (isset($additionalData['gateway_response'])) {
                $query .= ", gateway_response = ?";
                $params[] = $additionalData['gateway_response'];
                $types .= "s";
            }
            
            $query .= " WHERE id = ?";
            $params[] = $paymentId;
            $types .= "i";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("PaymentService: Failed to update payment status - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get payment by ID
     * 
     * @param int $paymentId Payment ID
     * @return array|null Payment record
     */
    public function getPaymentById($paymentId) {
        try {
            $conn = $this->db->getConnection();
            
            $query = "SELECT * FROM payments WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $paymentId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("PaymentService: Failed to get payment by ID - " . $e->getMessage());
            return null;
        }
    }
}
?>
