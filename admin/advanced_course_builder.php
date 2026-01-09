<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
require_once dirname(__DIR__) . '/models/User.php';

requireInstructor();

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($courseId <= 0) {
    $_SESSION['error_message'] = 'Invalid course';
    redirect('courses.php');
}

$courseModel = new Course();
$userModel = new User();

$course = $courseModel->getCourseById($courseId);
if (!$course) {
    $_SESSION['error_message'] = 'Course not found';
    redirect('courses.php');
}

// Instructors can only edit their own courses
if (getUserRole() === 'instructor' && (int)$course['instructor_id'] !== (int)($_SESSION['user_id'] ?? 0)) {
    $_SESSION['error_message'] = 'Access denied. You can only edit your own courses.';
    redirect('courses.php');
}

$meta = $courseModel->getCourseMeta($courseId);
$whatYouLearn = $meta['what_you_learn'] ?? [];
$requirements = $meta['requirements'] ?? [];
$targetAudience = $meta['target_audience'] ?? [];
$faqs = $meta['faqs'] ?? [];

if (!is_array($whatYouLearn)) $whatYouLearn = [];
if (!is_array($requirements)) $requirements = [];
if (!is_array($targetAudience)) $targetAudience = [];
if (!is_array($faqs)) $faqs = [];

$courseStats = $courseModel->getCourseStatistics($courseId);
$db = new Database();
$conn = $db->getConnection();
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$instructors = $userModel->getInstructors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Builder - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .builder-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-radius: 12px;
        }
        .list-editor-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .list-editor-item input {
            flex: 1;
        }
        .lesson-row {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
            background: #fff;
        }
        .lesson-row.dragging {
            opacity: 0.6;
        }
        .drag-handle {
            cursor: grab;
            color: #adb5bd;
        }
        .video-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        .video-upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .video-upload-area.dragover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        .video-preview {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 10px;
        }
        .google-drive-preview {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="instructorDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-chalkboard-teacher me-1"></i> Instructor
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="courses.php">My Courses</a></li>
                        <li><a class="dropdown-item" href="analytics.php">Analytics</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book-open me-2"></i> My Courses
                    </a>
                    <a href="course_builder.php?id=<?php echo $courseId; ?>" class="list-group-item list-group-item-action active">
                        <i class="fas fa-cogs me-2"></i> Course Builder
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Course Builder</h1>
                    <div>
                        <span class="badge bg-danger">Instructor</span>
                    </div>
                </div>

                <div class="card builder-card mb-4">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <div class="d-flex gap-2 mb-3">
                            <span class="badge bg-primary"><i class="fas fa-signal me-1"></i><?php echo htmlspecialchars(ucfirst($course['difficulty_level'])); ?></span>
                            <span class="badge bg-info"><i class="fas fa-circle me-1"></i><?php echo htmlspecialchars(ucfirst($course['status'])); ?></span>
                            <span class="badge bg-success"><i class="fas fa-users me-1"></i><?php echo (int)($courseStats['total_enrollments'] ?? 0); ?> students</span>
                            <span class="badge bg-warning"><i class="fas fa-list me-1"></i><?php echo (int)($courseStats['total_lessons'] ?? 0); ?> lessons</span>
                        </div>
                    </div>
                </div>

                <div class="card builder-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Curriculum</strong>
                        <button class="btn btn-sm btn-success" id="btnNewLesson"><i class="fas fa-plus me-1"></i>New Lesson</button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            Drag lessons by the handle to reorder, then click <strong>Save Order</strong>.
                        </div>
                        <div id="lessonsList"></div>
                        <button class="btn btn-outline-primary" id="btnSaveOrder"><i class="fas fa-sort me-2"></i>Save Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Lesson Modal -->
    <div class="modal fade" id="lessonModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lessonModalTitle">Advanced Lesson Editor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formLesson" enctype="multipart/form-data">
                        <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                        <input type="hidden" name="lesson_id" id="lesson_id" value="">

                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary"><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Lesson Title *</label>
                                <input class="form-control" name="title" id="lesson_title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Duration (minutes) *</label>
                                <input type="number" class="form-control" name="duration_minutes" id="lesson_duration" min="1" required>
                            </div>
                        </div>

                        <!-- Lesson Type and Settings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary"><i class="fas fa-cog me-2"></i>Lesson Settings</h6>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Lesson Type *</label>
                                <select class="form-select" name="lesson_type" id="lesson_type" required>
                                    <option value="video">Video</option>
                                    <option value="text">Text</option>
                                    <option value="quiz">Quiz</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Free Preview</label>
                                <select class="form-select" name="is_free" id="lesson_is_free">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Video Quality</label>
                                <select class="form-select" name="video_quality" id="video_quality">
                                    <option value="360p">360p</option>
                                    <option value="480p">480p</option>
                                    <option value="720p" selected>720p</option>
                                    <option value="1080p">1080p</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Downloadable</label>
                                <select class="form-select" name="is_downloadable" id="is_downloadable">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>

                        <!-- Video Content Section -->
                        <div id="videoContentSection" class="mb-4">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary"><i class="fas fa-video me-2"></i>Video Content</h6>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Video Source</label>
                                    <select class="form-select" name="video_source" id="video_source">
                                        <option value="none">No Video</option>
                                        <option value="upload">Upload Video File</option>
                                        <option value="google_drive">Google Drive URL</option>
                                        <option value="external_url">External Video URL</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Video Upload Section -->
                            <div id="videoUploadSection" style="display: none;">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Upload Video File</label>
                                        <div class="video-upload-area" id="videoUploadArea">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                            <p class="mb-2">Drag and drop your video file here or click to browse</p>
                                            <p class="text-muted small">Supported formats: MP4, WebM, OGG (Max: 500MB)</p>
                                            <input type="file" name="video_file" id="video_file" accept="video/*" style="display: none;">
                                            <button type="button" class="btn btn-outline-primary" id="btnSelectVideo">
                                                <i class="fas fa-folder-open me-2"></i>Select Video
                                            </button>
                                        </div>
                                        <div id="videoPreview" style="display: none;">
                                            <video class="video-preview" controls></video>
                                            <div class="mt-2">
                                                <span id="videoFileName" class="text-muted"></span>
                                                <span id="videoFileSize" class="text-muted ms-3"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Google Drive Section -->
                            <div id="googleDriveSection" style="display: none;">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Google Drive Video URL</label>
                                        <input type="url" class="form-control" name="google_drive_url" id="google_drive_url" 
                                               placeholder="https://drive.google.com/file/d/FILE_ID/view">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Share your Google Drive video with "Anyone with the link" access and paste the URL here
                                        </div>
                                        <div id="googleDrivePreview" class="google-drive-preview" style="display: none;">
                                            <div class="d-flex align-items-center">
                                                <i class="fab fa-google-drive fa-2x text-success me-3"></i>
                                                <div>
                                                    <div class="fw-semibold">Google Drive Video</div>
                                                    <div id="googleDriveFileName" class="text-muted small"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- External URL Section -->
                            <div id="externalUrlSection" style="display: none;">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">External Video URL</label>
                                        <input type="url" class="form-control" name="video_url" id="lesson_video_url" 
                                               placeholder="https://example.com/video.mp4">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Direct link to video file (MP4, WebM, etc.) or streaming URL
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Text Content Section -->
                        <div class="mb-4">
                            <h6 class="text-primary"><i class="fas fa-file-alt me-2"></i>Text Content</h6>
                            <div class="mb-3">
                                <label class="form-label">Lesson Content</label>
                                <textarea class="form-control" name="content" id="lesson_content" rows="8" 
                                          placeholder="Enter your lesson content here..."></textarea>
                            </div>
                        </div>

                        <!-- Advanced Options -->
                        <div class="mb-4">
                            <h6 class="text-primary"><i class="fas fa-sliders-h me-2"></i>Advanced Options</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="auto_generate_thumbnail" 
                                               id="auto_generate_thumbnail" checked>
                                        <label class="form-check-label" for="auto_generate_thumbnail">
                                            Auto-generate thumbnail from video
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="process_video" 
                                               id="process_video" checked>
                                        <label class="form-check-label" for="process_video">
                                            Process video for optimal streaming
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" id="btnDeleteLesson" style="display:none;">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btnSaveLesson">
                        <i class="fas fa-save me-2"></i>Save Lesson
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        const COURSE_ID = <?php echo $courseId; ?>;
        const API_BASE = '../api';
        
        const initialMeta = {
            what_you_learn: <?php echo json_encode($whatYouLearn); ?>,
            requirements: <?php echo json_encode($requirements); ?>,
            target_audience: <?php echo json_encode($targetAudience); ?>,
            faqs: <?php echo json_encode($faqs); ?>,
            lessons: []
        };

        const state = {
            what_you_learn: Array.isArray(initialMeta.what_you_learn) ? [...initialMeta.what_you_learn] : [],
            requirements: Array.isArray(initialMeta.requirements) ? [...initialMeta.requirements] : [],
            target_audience: Array.isArray(initialMeta.target_audience) ? [...initialMeta.target_audience] : [],
            faqs: Array.isArray(initialMeta.faqs) ? [...initialMeta.faqs] : [],
            lessons: []
        };

        // Video upload handling
        const videoUploadArea = document.getElementById('videoUploadArea');
        const videoFileInput = document.getElementById('video_file');
        const videoPreview = document.getElementById('videoPreview');
        const videoPreviewElement = videoPreview.querySelector('video');
        const btnSelectVideo = document.getElementById('btnSelectVideo');

        btnSelectVideo.addEventListener('click', () => videoFileInput.click());
        
        videoUploadArea.addEventListener('click', () => videoFileInput.click());
        
        videoUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            videoUploadArea.classList.add('dragover');
        });

        videoUploadArea.addEventListener('dragleave', () => {
            videoUploadArea.classList.remove('dragover');
        });

        videoUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            videoUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleVideoFile(files[0]);
            }
        });

        videoFileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleVideoFile(e.target.files[0]);
            }
        });

        function handleVideoFile(file) {
            if (!file.type.startsWith('video/')) {
                alert('Please select a valid video file.');
                return;
            }

            if (file.size > 500 * 1024 * 1024) { // 500MB
                alert('Video file size must be less than 500MB.');
                return;
            }

            const url = URL.createObjectURL(file);
            videoPreviewElement.src = url;
            document.getElementById('videoFileName').textContent = file.name;
            document.getElementById('videoFileSize').textContent = formatFileSize(file.size);
            videoPreview.style.display = 'block';
            videoUploadArea.style.display = 'none';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Video source handling
        document.getElementById('video_source').addEventListener('change', (e) => {
            const source = e.target.value;
            document.getElementById('videoUploadSection').style.display = source === 'upload' ? 'block' : 'none';
            document.getElementById('googleDriveSection').style.display = source === 'google_drive' ? 'block' : 'none';
            document.getElementById('externalUrlSection').style.display = source === 'external_url' ? 'block' : 'none';
        });

        // Google Drive URL validation
        document.getElementById('google_drive_url').addEventListener('input', (e) => {
            const url = e.target.value;
            const preview = document.getElementById('googleDrivePreview');
            
            if (url && url.includes('drive.google.com')) {
                const fileId = extractGoogleDriveFileId(url);
                if (fileId) {
                    preview.style.display = 'block';
                    document.getElementById('googleDriveFileName').textContent = `File ID: ${fileId}`;
                }
            } else {
                preview.style.display = 'none';
            }
        });

        function extractGoogleDriveFileId(url) {
            const match = url.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
            return match ? match[1] : null;
        }

        // Lesson type handling
        document.getElementById('lesson_type').addEventListener('change', (e) => {
            const isVideoLesson = e.target.value === 'video';
            document.getElementById('videoContentSection').style.display = isVideoLesson ? 'block' : 'none';
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadLessons();
            
            // Set initial video content visibility
            const lessonType = document.getElementById('lesson_type').value;
            document.getElementById('videoContentSection').style.display = lessonType === 'video' ? 'block' : 'none';
        });

        async function loadLessons() {
            try {
                const response = await fetch(`${API_BASE}/admin_course_lessons.php?course_id=${COURSE_ID}`);
                const data = await response.json();
                state.lessons = data.lessons || [];
                renderLessons();
            } catch (error) {
                console.error('Error loading lessons:', error);
            }
        }

        function lessonTypeBadge(type) {
            if (type === 'video') return 'primary';
            if (type === 'quiz') return 'warning';
            return 'secondary';
        }

        function renderLessons() {
            const root = document.getElementById('lessonsList');
            root.innerHTML = '';

            state.lessons.forEach((lesson) => {
                const row = document.createElement('div');
                row.className = 'lesson-row';
                row.draggable = true;
                row.dataset.id = lesson.id;

                const left = document.createElement('div');
                left.className = 'd-flex align-items-start gap-3';

                const handle = document.createElement('div');
                handle.className = 'drag-handle pt-1 text-muted';
                handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';

                const meta = document.createElement('div');
                meta.className = 'flex-grow-1';
                meta.innerHTML = `
                    <div class="fw-semibold">${lesson.title}</div>
                    <div class="small text-muted">Type: ${lesson.lesson_type} • Duration: ${lesson.duration_minutes}m • Order: ${lesson.lesson_order}</div>
                `;

                const actions = document.createElement('div');
                actions.className = 'd-flex gap-2';
                
                const badge = document.createElement('span');
                badge.className = `badge bg-${lessonTypeBadge(lesson.lesson_type)}`;
                badge.textContent = lesson.lesson_type;
                actions.appendChild(badge);

                const btnEdit = document.createElement('button');
                btnEdit.className = 'btn btn-sm btn-outline-primary';
                btnEdit.type = 'button';
                btnEdit.innerHTML = '<i class="fas fa-pen"></i>';
                btnEdit.addEventListener('click', () => openLessonModal(lesson));
                actions.appendChild(btnEdit);

                left.appendChild(handle);
                left.appendChild(meta);
                left.appendChild(actions);
                row.appendChild(left);
                root.appendChild(row);
            });
        }

        function openLessonModal(lesson = null) {
            const modal = new bootstrap.Modal(document.getElementById('lessonModal'));
            const form = document.getElementById('formLesson');
            
            if (lesson) {
                document.getElementById('lessonModalTitle').textContent = 'Edit Lesson';
                document.getElementById('lesson_id').value = lesson.id;
                document.getElementById('lesson_title').value = lesson.title;
                document.getElementById('lesson_type').value = lesson.lesson_type;
                document.getElementById('lesson_duration').value = lesson.duration_minutes;
                document.getElementById('lesson_is_free').value = lesson.is_free;
                document.getElementById('lesson_content').value = lesson.content || '';
                document.getElementById('btnDeleteLesson').style.display = 'inline-block';
            } else {
                document.getElementById('lessonModalTitle').textContent = 'Add New Lesson';
                form.reset();
                document.getElementById('lesson_id').value = '';
                document.getElementById('btnDeleteLesson').style.display = 'none';
                videoPreview.style.display = 'none';
                videoUploadArea.style.display = 'block';
            }
            
            modal.show();
        }

        // Event listeners
        document.getElementById('btnNewLesson').addEventListener('click', () => openLessonModal());

        document.getElementById('btnSaveLesson').addEventListener('click', async () => {
            const form = document.getElementById('formLesson');
            const formData = new FormData(form);
            
            try {
                const response = await fetch(`${API_BASE}/admin_course_lessons.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('lessonModal')).hide();
                    loadLessons();
                } else {
                    alert(result.message || 'Error saving lesson');
                }
            } catch (error) {
                console.error('Error saving lesson:', error);
                alert('Error saving lesson');
            }
        });

        // Modal event listeners
        document.getElementById('lessonModal').addEventListener('show.bs.modal', () => {
            document.body.style.paddingRight = '0px';
        });

        document.getElementById('lessonModal').addEventListener('hidden.bs.modal', () => {
            document.body.style.paddingRight = '';
        });
    </script>
</body>
</html>
