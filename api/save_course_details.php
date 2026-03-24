<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Course.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$courseId = intval($_POST['course_id']);

// Verify course ownership
$course = new Course();
$courseData = $course->getCourseById($courseId);

if (!$courseData) {
    sendJSON(['success' => false, 'message' => 'Course not found']);
}

$role = getUserRole();
if ($role !== 'admin' && ($role === 'instructor' && (int)$courseData['instructor_id'] !== (int)$_SESSION['user_id'])) {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

// Get course meta data
$whatYouLearn = json_decode($_POST['what_you_learn'] ?? '[]', true);
$requirements = json_decode($_POST['requirements'] ?? '[]', true);
$targetAudience = json_decode($_POST['target_audience'] ?? '[]', true);
$faqs = json_decode($_POST['faqs'] ?? '[]', true);

// Save course meta
$conn = connectDB();

// Delete existing meta
$stmt = $conn->prepare("DELETE FROM course_meta WHERE course_id = ?");
$stmt->bind_param("i", $courseId);
$stmt->execute();

// Insert new meta
$stmt = $conn->prepare("INSERT INTO course_meta (course_id, meta_key, meta_value) VALUES (?, ?, ?)");

// Save what you learn
foreach ($whatYouLearn as $item) {
    if (!empty(trim($item))) {
        $stmt->bind_param("iss", $courseId, 'what_you_learn', $item);
        $stmt->execute();
    }
}

// Save requirements
foreach ($requirements as $item) {
    if (!empty(trim($item))) {
        $stmt->bind_param("iss", $courseId, 'requirement', $item);
        $stmt->execute();
    }
}

// Save target audience
foreach ($targetAudience as $item) {
    if (!empty(trim($item))) {
        $stmt->bind_param("iss", $courseId, 'target_audience', $item);
        $stmt->execute();
    }
}

// Save FAQs
foreach ($faqs as $faq) {
    if (!empty(trim($faq['question'])) && !empty(trim($faq['answer']))) {
        $stmt->bind_param("iss", $courseId, 'faq', json_encode($faq));
        $stmt->execute();
    }
}

sendJSON(['success' => true, 'message' => 'Course details saved successfully']);
?>
