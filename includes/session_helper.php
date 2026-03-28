<?php
/**
 * Session Helper - Centralized Session Management
 * This file ensures sessions are properly initialized across all pages
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Initialize session if not already started
 * This function should be called at the beginning of every page
 */
function initializeSession() {
    // Session is already started in config.php, but this ensures
    // it's available for all pages that include this helper
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get user display name
 */
function getUserDisplayName() {
    return $_SESSION['full_name'] ?? $_SESSION['user']['full_name'] ?? 'Guest';
}

/**
 * Set session data for logged in user
 */
function setSessionData($userData) {
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_role'] = $userData['role'];
    $_SESSION['full_name'] = $userData['full_name'];
    $_SESSION['user'] = $userData;
    $_SESSION['last_activity'] = time();
    $_SESSION['expires_on'] = time() + 1800; // 30 minutes
}

/**
 * Clear session data (logout)
 */
function clearSessionData() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_role']);
    unset($_SESSION['full_name']);
    unset($_SESSION['user']);
    unset($_SESSION['last_activity']);
    unset($_SESSION['expires_on']);
}

/**
 * Check if session has expired
 */
function isSessionExpired() {
    return isset($_SESSION['expires_on']) && time() > $_SESSION['expires_on'];
}

/**
 * Update session activity timestamp
 */
function updateSessionActivity() {
    if (isUserLoggedIn()) {
        $_SESSION['last_activity'] = time();
        $_SESSION['expires_on'] = time() + 1800; // Reset 30 minute timeout
    }
}

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return $message;
}

/**
 * Check if there's a flash message to display
 */
function hasFlashMessage() {
    return isset($_SESSION['flash_message']);
}

// Auto-initialize session when this file is included
initializeSession();
?>
