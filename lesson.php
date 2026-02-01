<?php
// Redirect to the actual lesson page in the student directory
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirectUrl = 'student/lesson.php' . ($queryString ? '?' . $queryString : '');
header("Location: $redirectUrl");
exit;
?>