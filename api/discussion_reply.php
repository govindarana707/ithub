<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Discussion.php';

// Check if this is an API request or GUI request
$isApiRequest = isset($_POST['action']) || isset($_GET['api']);

// Authentication check
if (!isLoggedIn()) {
    if ($isApiRequest) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    } else {
        header('Location: ../login.php');
        exit;
    }
}

$userRole = getUserRole();
$userId = $_SESSION['user_id'];

// Role-based access control
if ($userRole !== 'student' && $userRole !== 'instructor') {
    if ($isApiRequest) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    } else {
        $_SESSION['error_message'] = 'Access denied';
        header('Location: ../dashboard.php');
        exit;
    }
}

$action = $_POST['action'] ?? '';
$discussion = new Discussion();

// Handle API requests
if ($isApiRequest) {
    header('Content-Type: application/json');
    
    try {
        switch ($action) {
            case 'create_reply':
                $discussionId = intval($_POST['discussion_id'] ?? 0);
                $content = sanitize($_POST['content'] ?? '');
                
                if ($discussionId <= 0 || empty($content)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid input']);
                    exit;
                }
                
                // Check if student has access to the discussion
                $discussionDetails = $discussion->getDiscussionById($discussionId);
                if (!$discussionDetails) {
                    echo json_encode(['success' => false, 'message' => 'Discussion not found']);
                    exit;
                }
                
                // Check access based on user role
                $conn = connectDB();
                $hasAccess = false;
                
                if ($userRole === 'student') {
                    // Check if student is enrolled in the course
                    $stmt = $conn->prepare("SELECT COUNT(*) as enrolled FROM enrollments_new WHERE user_id = ? AND course_id = ? AND status = 'active'");
                    if ($stmt === false) {
                        $hasAccess = false;
                    } else {
                        $stmt->bind_param("ii", $userId, $discussionDetails['course_id']);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        $hasAccess = $result['enrolled'] > 0;
                    }
                } elseif ($userRole === 'instructor') {
                    // Check if instructor owns the course
                    $stmt = $conn->prepare("SELECT COUNT(*) as owns FROM courses_new WHERE id = ? AND instructor_id = ?");
                    $stmt->bind_param("ii", $discussionDetails['course_id'], $userId);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $hasAccess = $result['owns'] > 0;
                }
                
                if (!$hasAccess) {
                    echo json_encode(['success' => false, 'message' => 'You do not have access to this discussion']);
                    exit;
                }
                
                $data = [
                    'course_id' => $discussionDetails['course_id'],
                    'student_id' => $userId,
                    'title' => '', // Replies don't have titles
                    'content' => $content,
                    'parent_id' => $discussionId
                ];
                
                $result = $discussion->createDiscussion($data);
                
                if ($result['success']) {
                    echo json_encode(['success' => true, 'message' => 'Reply posted successfully', 'reply_id' => $result['discussion_id']]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to post reply: ' . $result['error']]);
                }
                break;
                
            case 'get_discussions':
                $conn = connectDB();
                $stmt = $conn->prepare("
                    SELECT d.*, c.title as course_title, u.username, u.full_name
                    FROM discussions d
                    JOIN courses_new c ON d.course_id = c.id
                    JOIN users_new u ON d.student_id = u.id
                    WHERE d.parent_id IS NULL
                    ORDER BY d.created_at DESC
                    LIMIT 50
                ");
                $stmt->execute();
                $discussions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'discussions' => $discussions]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// GUI Interface
require_once '../includes/universal_header.php';

// Get discussions for dropdown
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT d.*, c.title as course_title, u.username
    FROM discussions d
    JOIN courses_new c ON d.course_id = c.id
    JOIN users_new u ON d.student_id = u.id
    WHERE d.parent_id IS NULL
    ORDER BY d.created_at DESC
    LIMIT 50
");
$stmt->execute();
$discussions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Reply - API Test Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --card-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 30px 30px;
        }
        
        .dashboard-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .api-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: none;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .api-card:hover {
            transform: translateY(-5px);
        }
        
        .api-card .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border: none;
        }
        
        .api-card .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .response-area {
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 12px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            min-height: 200px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .response-area.success {
            border-left: 4px solid #10b981;
        }
        
        .response-area.error {
            border-left: 4px solid #ef4444;
        }
        
        .method-badge {
            background: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .endpoint-url {
            background: #f1f3f5;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: monospace;
            color: #495057;
        }
        
        .info-card {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-indicator.online {
            background: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.3);
        }
        
        .discussion-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .json-key { color: #9cdcfe; }
        .json-string { color: #ce9178; }
        .json-number { color: #b5cea8; }
        .json-boolean { color: #569cd6; }
        
        /* Visual Response Card Styles */
        .response-card {
            display: flex;
            align-items: center;
            padding: 1.5rem;
            border-radius: 16px;
            transition: all 0.3s ease;
            animation: slideIn 0.4s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .response-card.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
        }
        
        .response-card.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 2px solid #dc3545;
        }
        
        .response-icon {
            font-size: 3rem;
            margin-right: 1.5rem;
        }
        
        .response-card.success .response-icon {
            color: #28a745;
        }
        
        .response-card.error .response-icon {
            color: #dc3545;
        }
        
        .response-content {
            flex: 1;
        }
        
        .response-content h5 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .response-card.success .response-content h5 {
            color: #155724;
        }
        
        .response-card.error .response-content h5 {
            color: #721c24;
        }
        
        .response-content p {
            margin-bottom: 0.75rem;
            color: #495057;
        }
        
        .response-meta {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .response-meta .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .response-card.success .response-meta .badge {
            background: #28a745;
            color: white;
        }
        
        .response-card.error .response-meta .badge {
            background: #dc3545;
            color: white;
        }
        
        #statusText.success {
            color: #28a745;
        }
        
        #statusText.error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-comments me-2"></i>Discussion Reply API</h1>
                    <p class="mb-0">Interactive API Testing Interface</p>
                </div>
                <div class="text-end">
                    <span class="status-indicator online"></span>
                    <span>API Online</span>
                    <div class="mt-2">
                        <span class="badge bg-light text-dark">Role: <?php echo ucfirst($userRole); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Info Card -->
        <div class="info-card">
            <div class="d-flex align-items-start">
                <i class="fas fa-info-circle text-primary me-3 mt-1"></i>
                <div>
                    <h5 class="mb-2">API Endpoint Information</h5>
                    <div class="d-flex align-items-center mb-2">
                        <span class="method-badge me-2">POST</span>
                        <span class="endpoint-url">api/discussion_reply.php</span>
                    </div>
                    <p class="mb-0 text-muted">This interface allows you to test the discussion reply API endpoint. Select a discussion and post a reply to see the API response.</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Request Form -->
            <div class="col-md-6">
                <div class="api-card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-paper-plane me-2"></i>Send Request</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="replyForm">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-list me-1"></i>Select Discussion
                                </label>
                                <select class="form-select" id="discussion_id" name="discussion_id" required>
                                    <option value="">Choose a discussion...</option>
                                    <?php foreach ($discussions as $disc): ?>
                                        <option value="<?php echo $disc['id']; ?>" data-course="<?php echo htmlspecialchars($disc['course_title']); ?>" data-author="<?php echo htmlspecialchars($disc['username']); ?>">
                                            <?php echo htmlspecialchars(substr($disc['title'], 0, 50)); ?>... (<?php echo htmlspecialchars($disc['course_title']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="discussion-preview" id="discussionPreview" style="display: none;">
                                <strong>Course:</strong> <span id="previewCourse"></span><br>
                                <strong>Author:</strong> <span id="previewAuthor"></span>
                            </div>
                            
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-edit me-1"></i>Reply Content
                                </label>
                                <textarea class="form-control" id="content" name="content" rows="6" placeholder="Enter your reply content here..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Reply
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Response Area -->
            <div class="col-md-6">
                <div class="api-card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-terminal me-2"></i>API Response</h4>
                    </div>
                    <div class="card-body p-4">
                        <!-- Visual Response Card -->
                        <div id="visualResponse" style="display: none;">
                            <div class="response-card" id="responseCard">
                                <div class="response-icon">
                                    <i class="fas fa-check-circle" id="responseIcon"></i>
                                </div>
                                <div class="response-content">
                                    <h5 id="responseTitle">Success!</h5>
                                    <p id="responseMessage">Reply posted successfully</p>
                                    <div class="response-meta">
                                        <span class="badge" id="responseBadge">ID: 11</span>
                                        <span class="text-muted" id="responseTimeDisplay">Time: 45ms</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Raw JSON (Collapsible) -->
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#rawJson">
                                <i class="fas fa-code me-1"></i>Show Raw JSON
                            </button>
                            <div class="collapse mt-2" id="rawJson">
                                <div id="responseArea" class="response-area">
                                    <span class="text-muted">Response will appear here...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <small class="text-muted">Status: <span id="statusText" class="fw-semibold">-</span></small>
                            <button class="btn btn-sm btn-outline-secondary" onclick="clearResponse()">
                                <i class="fas fa-trash me-1"></i>Clear
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="api-card">
                    <div class="card-header bg-secondary text-white">
                        <h4><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-grid gap-2">
                            <a href="../student/discussions.php" class="btn btn-outline-primary">
                                <i class="fas fa-comments me-2"></i>Go to Discussions
                            </a>
                            <button class="btn btn-outline-info" onclick="loadSampleData()">
                                <i class="fas fa-magic me-2"></i>Load Sample Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Discussion preview update
        document.getElementById('discussion_id').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const preview = document.getElementById('discussionPreview');
            
            if (this.value) {
                document.getElementById('previewCourse').textContent = selected.dataset.course;
                document.getElementById('previewAuthor').textContent = selected.dataset.author;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });
        
        // Form submission
        document.getElementById('replyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'create_reply');
            formData.append('discussion_id', document.getElementById('discussion_id').value);
            formData.append('content', document.getElementById('content').value);
            
            const startTime = Date.now();
            
            try {
                const response = await fetch('discussion_reply.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                const responseTime = Date.now() - startTime;
                
                displayResponse(data, responseTime);
            } catch (error) {
                displayResponse({ success: false, message: error.message }, 0, true);
            }
        });
        
        function displayResponse(data, time, isError = false) {
            const visualDiv = document.getElementById('visualResponse');
            const responseCard = document.getElementById('responseCard');
            const responseIcon = document.getElementById('responseIcon');
            const responseTitle = document.getElementById('responseTitle');
            const responseMessage = document.getElementById('responseMessage');
            const responseBadge = document.getElementById('responseBadge');
            const responseTimeDisplay = document.getElementById('responseTimeDisplay');
            const statusText = document.getElementById('statusText');
            const rawArea = document.getElementById('responseArea');
            
            // Show visual response
            visualDiv.style.display = 'block';
            
            // Update visual card based on success/error
            if (data.success) {
                responseCard.className = 'response-card success';
                responseIcon.className = 'fas fa-check-circle';
                responseTitle.textContent = 'Success!';
                statusText.textContent = '✓ Completed';
                statusText.className = 'fw-semibold success';
            } else {
                responseCard.className = 'response-card error';
                responseIcon.className = 'fas fa-times-circle';
                responseTitle.textContent = 'Error!';
                statusText.textContent = '✗ Failed';
                statusText.className = 'fw-semibold error';
            }
            
            responseMessage.textContent = data.message || (data.success ? 'Operation completed successfully' : 'An error occurred');
            responseBadge.textContent = data.reply_id ? 'Reply ID: ' + data.reply_id : '';
            responseBadge.style.display = data.reply_id ? 'inline-block' : 'none';
            responseTimeDisplay.textContent = time + 'ms';
            
            // Update raw JSON area
            rawArea.innerHTML = syntaxHighlight(JSON.stringify(data, null, 2));
        }
        
        function syntaxHighlight(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/(".*?"):\s*(".*?"|\d+|true|false|null)/g, function(match, key, value) {
                let valueClass = 'json-string';
                if (/^\d+$/.test(value)) valueClass = 'json-number';
                else if (/^(true|false)$/.test(value)) valueClass = 'json-boolean';
                return '<span class="json-key">' + key + '</span>: <span class="' + valueClass + '">' + value + '</span>';
            });
        }
        
        function clearResponse() {
            document.getElementById('visualResponse').style.display = 'none';
            document.getElementById('responseArea').innerHTML = '<span class="text-muted">Response will appear here...</span>';
            document.getElementById('statusText').textContent = '-';
            document.getElementById('statusText').className = 'fw-semibold';
        }
        
        function loadSampleData() {
            document.getElementById('content').value = 'This is a sample reply message for testing the API endpoint. The reply system is working correctly!';
        }
    </script>
</body>
</html>
<?php
exit;
?>
