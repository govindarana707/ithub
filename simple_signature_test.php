<?php
echo "<h1>🔧 Simple Signature Test</h1>";

// Test signature generation
$secretKey = '8gBm/:&EnhH.1/q(';
$message = 'total_amount=100,transaction_uuid=11-201-13,product_code=EPAYTEST';

echo "<h2>Test Data:</h2>";
echo "<div><strong>Secret Key:</strong> " . htmlspecialchars($secretKey) . "</div>";
echo "<div><strong>Message:</strong> " . htmlspecialchars($message) . "</div>";

// Generate signature
$hmacHash = hash_hmac('sha256', $message, $secretKey, true);
$signature = base64_encode($hmacHash);

echo "<h2>Generated Signature:</h2>";
echo "<div>" . htmlspecialchars($signature) . "</div>";

// Expected from eSewa docs
$expected = '4Ov7pCI1zIOdwtV2BRMUNjz1upIlT/COTxfLhWvVurE=';

echo "<h2>Expected from eSewa:</h2>";
echo "<div>" . htmlspecialchars($expected) . "</div>";

// Compare
if ($signature === $expected) {
    echo "<div style='color: green; font-size: 18px;'>✅ PERFECT MATCH!</div>";
} else {
    echo "<div style='color: red; font-size: 18px;'>❌ NO MATCH</div>";
    echo "<div>Our signature: " . htmlspecialchars($signature) . "</div>";
    echo "<div>Expected: " . htmlspecialchars($expected) . "</div>";
}

// Test with actual payment data
echo "<h2>Test with Payment Data:</h2>";

$paymentData = [
    'amount' => 89,
    'tax_amount' => 0,
    'total_amount' => 89,
    'transaction_uuid' => 'TEST-' . time(),
    'product_code' => 'EPAYTEST',
    'product_service_charge' => 0,
    'product_delivery_charge' => 0,
    'success_url' => 'http://localhost/store/test_callback.php',
    'failure_url' => 'http://localhost/store/test_callback.php',
    'signed_field_names' => 'total_amount,transaction_uuid,product_code'
];

// Generate signature for payment
$paymentMessage = sprintf(
    "total_amount=%s,transaction_uuid=%s,product_code=%s",
    $paymentData['total_amount'],
    $paymentData['transaction_uuid'],
    $paymentData['product_code']
);

$paymentSignature = base64_encode(hash_hmac('sha256', $paymentMessage, $secretKey, true));

echo "<h3>Payment Message:</h3>";
echo "<div>" . htmlspecialchars($paymentMessage) . "</div>";
echo "<h3>Payment Signature:</h3>";
echo "<div>" . htmlspecialchars($paymentSignature) . "</div>";

// Create test form
echo "<h2>Test eSewa Form:</h2>";
echo "<form method='POST' action='https://rc-epay.esewa.com.np/api/epay/main/v2/form' target='_blank'>";
foreach ($paymentData as $key => $value) {
    echo "<input type='hidden' name='$key' value='" . htmlspecialchars($value) . "'>";
}
echo "<input type='hidden' name='signature' value='" . htmlspecialchars($paymentSignature) . "'>";
echo "<button type='submit' style='background: #00A651; color: white; padding: 15px 30px; border: none; cursor: pointer; font-size: 16px;'>🟢 Test with eSewa</button>";
echo "</form>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>1. Click the test button above</li>";
echo "<li>2. Use eSewa test credentials when prompted</li>";
echo "<li>3. Check if you get 'Invalid payload signature' error</li>";
echo "<li>4. If error persists, we need to find the correct signature format</li>";
echo "</ol>";

echo "<h2>eSewa Test Credentials:</h2>";
echo "<ul>";
echo "<li><strong>eSewa ID:</strong> 9806800001</li>";
echo "<li><strong>Password:</strong> Nepal@123</li>";
echo "<li><strong>Token:</strong> 123456</li>";
echo "</ul>";
?>
