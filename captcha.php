<?php
require_once __DIR__ . '/../includes/AuthEnhancements.php';

$auth = new AuthEnhancements();

// Generate new CAPTCHA
$captcha = $auth->generateCaptcha();

// Create simple CAPTCHA image (fallback method)
createSimpleCaptcha($captcha);
?>
