<?php
/**
 * Khalti Payment Integration
 * Official API Documentation: https://docs.khalti.com/khalti-epayment/
 * 
 * Flow:
 * 1. Initialize payment with amount, product details
 * 2. Get payment URL from Khalti
 * 3. Redirect user to Khalti for payment
 * 4. After payment, Khalti sends token to success URL
 * 5. Verify payment using the token
 * 6. Enroll user on successful verification
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../services/EnrollmentServiceNew.php';

header('Content-Type: application/json');

// Validate request
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue', 'code' => 'AUTH_REQUIRED']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method', 'code' => 'INVALID_METHOD']);
}

// Get and validate inputs
$courseId = intval($_POST['course_id'] ?? 0);
$studentId = $_SESSION['user_id'];

if ($courseId <= 0) {
    sendJSON(['success' => false, 'message' => 'Invalid course ID', 'code' => 'INVALID_COURSE']);
}

try {
    // Initialize services
    $enrollmentService = new EnrollmentServiceNew();
    
    // Get course details
    require_once __DIR__ . '/../models/Course.php';
    $course = new Course();
    $courseDetails = $course->getCourseById($courseId);

    if (!$courseDetails) {
        sendJSON(['success' => false, 'message' => 'Course not found', 'code' => 'COURSE_NOT_FOUND']);
    }

    if (isset($courseDetails['status']) && $courseDetails['status'] !== 'published') {
        sendJSON(['success' => false, 'message' => 'Course not available', 'code' => 'COURSE_UNAVAILABLE']);
    }

    // Check if already enrolled
    if ($enrollmentService->isUserEnrolled($studentId, $courseId)) {
        sendJSON(['success' => false, 'message' => 'Already enrolled', 'code' => 'ALREADY_ENROLLED']);
    }

    // Get Khalti configuration
    $khaltiConfig = getKhaltiConfig();
    $amount = floatval($courseDetails['price']) * 100; // Convert to paisa (1 NPR = 100 paisa)
    
    // If course is free, redirect to free trial enrollment
    if ($amount <= 0) {
        sendJSON([
            'success' => true,
            'free_course' => true,
            'redirect_url' => BASE_URL . 'api/enroll_course.php',
            'message' => 'This is a free course. Proceeding to enrollment.'
        ]);
    }

    // Generate unique product identity
    $productIdentity = 'course_' . $courseId . '_' . time();
    
    // Prepare Khalti payment initialization data
    $khaltiData = [
        'amount' => $amount,  // Amount in paisa
        'product_identity' => $productIdentity,
        'product_name' => $courseDetails['title'],
        'product_url' => BASE_URL . 'course-details.php?id=' . $courseId,
        'additional_info' => [
            'course_id' => $courseId,
            'user_id' => $studentId,
            'course_title' => $courseDetails['title'],
            'price' => $courseDetails['price']
        ],
        'public_key' => $khaltiConfig['public_key']
    ];

    // Create payment record in database first
    $conn = connectDB();
    $transactionId = 'KHLTI-' . uniqid() . '-' . time();
    
    $stmt = $conn->prepare("INSERT INTO payments (
        user_id, course_id, amount, currency, payment_method, 
        transaction_uuid, status, gateway_response, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $currency = 'NPR';
    $paymentMethod = 'khalti';
    $status = 'pending';
    $gatewayResponse = json_encode([
        'integration' => 'khalti',
        'amount_paisa' => $amount,
        'product_identity' => $productIdentity
    ]);
    
    $stmt->bind_param(
        "iidsssss",
        $studentId,
        $courseId,
        $amount,
        $currency,
        $paymentMethod,
        $transactionId,
        $status,
        $gatewayResponse
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create payment record");
    }
    
    $paymentId = $conn->insert_id;
    $stmt->close();
    $conn->close();

    // Generate payment URL with Khalti
    $paymentUrl = generateKhaltiPaymentUrl($khaltiData, $paymentId, $transactionId);
    
    if ($paymentUrl) {
        sendJSON([
            'success' => true,
            'payment_url' => $paymentUrl,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'message' => 'Redirecting to Khalti for payment'
        ]);
    } else {
        throw new Exception("Failed to generate Khalti payment URL");
    }
    
} catch (Exception $e) {
    error_log("Khalti payment API error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Payment service error', 'error' => $e->getMessage()]);
}

/**
 * Get Khalti configuration
 */
function getKhaltiConfig() {
    // Use live keys from Khalti dashboard
    $config = [
        'public_key' => '688ef743783f443abf185c344d988453',
        'secret_key' => 'e7e919ef979c4c8cbcb0cf33f7e2f0db',
        'test_mode' => false
    ];
    
    // Allow override from database settings
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM payment_settings WHERE setting_key LIKE 'khalti_%'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($row['setting_key'] === 'khalti_public_key' && !empty($row['setting_value'])) {
                $config['public_key'] = $row['setting_value'];
            } elseif ($row['setting_key'] === 'khalti_secret_key' && !empty($row['setting_value'])) {
                $config['secret_key'] = $row['setting_value'];
            } elseif ($row['setting_key'] === 'khalti_test_mode') {
                $config['test_mode'] = $row['setting_value'] === 'true';
            }
        }
        $stmt->close();
    }
    $conn->close();
    
    return $config;
}

/**
 * Generate Khalti payment URL
 * According to Khalti ePayment API
 */
function generateKhaltiPaymentUrl($khaltiData, $paymentId, $transactionId) {
    $baseUrl = 'https://khalti.com/api/v2/payment/';
    
    // Prepare the payment initiation request
    $postData = [
        'return_url' => BASE_URL . 'api/khalti_success.php?payment_id=' . $paymentId . '&transaction_id=' . $transactionId,
        'website_url' => BASE_URL,
        'amount' => $khaltiData['amount'],
        'product_identity' => $khaltiData['product_identity'],
        'product_name' => $khaltiData['product_name'],
        'product_url' => $khaltiData['product_url'],
        'additional_info' => json_encode($khaltiData['additional_info'])
    ];
    
    // Use cURL to call Khalti API
    $ch = curl_init($baseUrl . 'initiate/');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Key ' . $khaltiData['public_key'],
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Khalti cURL error: " . $error);
        return null;
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && isset($responseData['payment_url'])) {
        return $responseData['payment_url'];
    }
    
    // If API call fails, return Khalti's web payment URL with query params
    // This is a fallback approach
    error_log("Khalti API response: " . $response);
    
    // Fallback: Use Khalti web payment URL with query parameters
    $fallbackUrl = 'https://khalti.com/payment?' . http_build_query([
        'amount' => $khaltiData['amount'],
        'product_identity' => $khaltiData['product_identity'],
        'product_name' => $khaltiData['product_name'],
        'product_url' => $khaltiData['product_url']
    ]);
    
    return $fallbackUrl;
}
?>