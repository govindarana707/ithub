<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Discussion.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userRole = getUserRole();
$userId = $_SESSION['user_id'];

if ($userRole !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$page = max(1, intval($_GET['page'] ?? 1));
$filters = $_GET['filters'] ?? '';

$discussion = new Discussion();

try {
    // Parse filters
    parse_str($filters, $filterArray);
    
    $courseFilter = intval($filterArray['course'] ?? 0);
    $searchQuery = sanitize($filterArray['search'] ?? '');
    $statusFilter = sanitize($filterArray['status'] ?? '');
    $sortBy = sanitize($filterArray['sort'] ?? 'latest');
    $dateRange = sanitize($filterArray['date_range'] ?? '');
    $limit = intval($filterArray['limit'] ?? 20);
    
    // Get discussions
    if ($courseFilter && $searchQuery) {
        $discussions = $discussion->searchDiscussions($courseFilter, $searchQuery, $page, $limit);
    } elseif ($courseFilter) {
        $discussions = $discussion->getDiscussionsByCourse($courseFilter, $page, $limit);
    } else {
        $discussions = $discussion->getInstructorDiscussions($userId, $page, $limit);
    }
    
    // Apply filters
    if ($statusFilter || $dateRange) {
        $discussions = array_filter($discussions, function($discussion) use ($statusFilter, $dateRange) {
            if ($statusFilter === 'pinned' && !$discussion['is_pinned']) return false;
            if ($statusFilter === 'resolved' && !$discussion['is_resolved']) return false;
            if ($statusFilter === 'unresolved' && $discussion['is_resolved']) return false;
            
            if ($dateRange === 'today') {
                $today = date('Y-m-d');
                if (date('Y-m-d', strtotime($discussion['created_at'])) !== $today) return false;
            } elseif ($dateRange === 'week') {
                $weekAgo = date('Y-m-d', strtotime('-7 days'));
                if (date('Y-m-d', strtotime($discussion['created_at'])) < $weekAgo) return false;
            } elseif ($dateRange === 'month') {
                $monthAgo = date('Y-m-d', strtotime('-30 days'));
                if (date('Y-m-d', strtotime($discussion['created_at'])) < $monthAgo) return false;
            }
            
            return true;
        });
    }
    
    // Sort discussions
    switch ($sortBy) {
        case 'oldest':
            usort($discussions, function($a, $b) { return strtotime($a['created_at']) - strtotime($b['created_at']); });
            break;
        case 'most_replies':
            usort($discussions, function($a, $b) { return $b['reply_count'] - $a['reply_count']; });
            break;
        case 'least_replies':
            usort($discussions, function($a, $b) { return $a['reply_count'] - $b['reply_count']; });
            break;
        default: // latest
            usort($discussions, function($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });
    }
    
    echo json_encode([
        'success' => true,
        'discussions' => $discussions,
        'page' => $page,
        'has_more' => count($discussions) >= $limit
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading discussions: ' . $e->getMessage()
    ]);
}
?>
