<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Generate CSRF hidden input field
 */
function getCSRFInput() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

?>
