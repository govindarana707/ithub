<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if (getUserRole() !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$courseId = isset($_REQUEST['course_id']) ? (int)$_REQUEST['course_id'] : 0;
if ($courseId <= 0) {
    sendJSON(['success' => false, 'message' => 'Invalid course_id']);
}

$course = new Course();

if ($method === 'GET') {
    $meta = $course->getCourseMeta($courseId);
    sendJSON(['success' => true, 'meta' => $meta]);
}

if ($method !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    $payload = $_POST;
}

$meta = $payload['meta'] ?? null;
if (!is_array($meta)) {
    sendJSON(['success' => false, 'message' => 'Invalid meta payload']);
}

$ok = $course->setCourseMeta($courseId, $meta);
if (!$ok) {
    sendJSON(['success' => false, 'message' => 'Failed to save meta']);
}

sendJSON(['success' => true]);
