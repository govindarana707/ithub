<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Database.php';

requireInstructor();

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($courseId <= 0) {
    $_SESSION['error_message'] = 'Invalid course';
    redirect('instructor/courses.php');
}

$courseModel = new Course();
$userModel = new User();

$course = $courseModel->getCourseById($courseId);
if (!$course) {
    $_SESSION['error_message'] = 'Course not found';
    redirect('instructor/courses.php');
}

// Instructors and admins can edit courses (instructors only their own)
$userRole = getUserRole();
$userId = (int)($_SESSION['user_id'] ?? 0);
$courseInstructorId = (int)($course['instructor_id'] ?? 0);

if ($userRole === 'instructor' && $courseInstructorId !== $userId) {
    $_SESSION['error_message'] = 'You do not own this course (Course ID: ' . $courseId . ', Your ID: ' . $userId . '). Please select one of your own courses.';
    redirect('../instructor/courses.php');
    exit;
}
// Admin role can edit any course
$isAdmin = ($userRole === 'admin');

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
$categories = $conn->query("SELECT id, name FROM categories_new ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$instructors = $userModel->getInstructors();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Builder - <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .builder-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            border-radius: 12px;
            padding: 20px;
        }
        .builder-card {
            border-radius: 12px;
        }
        .list-editor-item {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
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
        }
        .badge-soft {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../instructor/dashboard.php"><i class="fas fa-graduation-cap me-2"></i>IT HUB</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../instructor/courses.php"><i class="fas fa-arrow-left me-1"></i>Back to Courses</a>
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="builder-header mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h2 class="mb-2">Course Builder</h2>
                    <div class="h4 mb-1"><?php echo htmlspecialchars($course['title']); ?></div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-soft"><i class="fas fa-layer-group me-1"></i><?php echo htmlspecialchars($course['category_name'] ?? ''); ?></span>
                        <span class="badge badge-soft"><i class="fas fa-signal me-1"></i><?php echo htmlspecialchars(ucfirst($course['difficulty_level'])); ?></span>
                        <span class="badge badge-soft"><i class="fas fa-circle me-1"></i><?php echo htmlspecialchars(ucfirst($course['status'])); ?></span>
                        <span class="badge badge-soft"><i class="fas fa-users me-1"></i><?php echo (int)($courseStats['total_enrollments'] ?? 0); ?> students</span>
                        <span class="badge badge-soft"><i class="fas fa-list me-1"></i><?php echo (int)($courseStats['total_lessons'] ?? 0); ?> lessons</span>
                    </div>
                </div>
                <div class="text-end">
                    <a class="btn btn-light" href="../course-details.php?id=<?php echo $courseId; ?>" target="_blank"><i class="fas fa-eye me-2"></i>Preview</a>
                    <button class="btn btn-warning" id="btnQuickSave"><i class="fas fa-save me-2"></i>Save All</button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card builder-card mb-4">
                    <div class="card-header"><strong>Basics</strong></div>
                    <div class="card-body">
                        <form id="formBasics" enctype="multipart/form-data">
                            <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">

                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input class="form-control" name="title" value="<?php echo htmlspecialchars($course['title']); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ((string)$cat['id'] === (string)$course['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if ($isAdmin): ?>
                            <div class="mb-3">
                                <label class="form-label">Instructor</label>
                                <select class="form-select" name="instructor_id">
                                    <?php foreach ($instructors as $inst): ?>
                                        <option value="<?php echo $inst['id']; ?>" <?php echo ((string)$inst['id'] === (string)$course['instructor_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($inst['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price (Rs)</label>
                                    <input type="number" step="0.01" class="form-control" name="price" value="<?php echo htmlspecialchars($course['price']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Duration (hours)</label>
                                    <input type="number" class="form-control" name="duration_hours" value="<?php echo htmlspecialchars($course['duration_hours']); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Difficulty</label>
                                    <select class="form-select" name="difficulty_level">
                                        <option value="beginner" <?php echo $course['difficulty_level'] === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                        <option value="intermediate" <?php echo $course['difficulty_level'] === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                        <option value="advanced" <?php echo $course['difficulty_level'] === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="draft" <?php echo $course['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo $course['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                        <option value="archived" <?php echo $course['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Thumbnail (upload)</label>
                                <input type="file" class="form-control" name="thumbnail_file" accept="image/*">
                                <div class="form-text">Uploading a new image will replace the current thumbnail.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Thumbnail URL (optional)</label>
                                <input class="form-control" name="thumbnail" value="<?php echo htmlspecialchars($course['thumbnail'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="5"><?php echo htmlspecialchars($course['description']); ?></textarea>
                            </div>

                            <button type="button" class="btn btn-primary w-100" id="btnSaveBasics"><i class="fas fa-save me-2"></i>Save Basics</button>
                        </form>
                    </div>
                </div>

                <div class="card builder-card">
                    <div class="card-header"><strong>Rich Details (Udemy-style)</strong></div>
                    <div class="card-body">
                        <form id="formMeta">
                            <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">

                            <div class="mb-3">
                                <label class="form-label">What you'll learn</label>
                                <div id="listWhatYouLearn"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLearn"><i class="fas fa-plus me-1"></i>Add</button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Requirements</label>
                                <div id="listRequirements"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddReq"><i class="fas fa-plus me-1"></i>Add</button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target audience</label>
                                <div id="listAudience"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddAud"><i class="fas fa-plus me-1"></i>Add</button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">FAQ</label>
                                <div id="listFaq"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddFaq"><i class="fas fa-plus me-1"></i>Add</button>
                            </div>

                            <button type="button" class="btn btn-warning w-100" id="btnSaveMeta"><i class="fas fa-save me-2"></i>Save Details</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
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

                <div class="card builder-card">
                    <div class="card-header"><strong>How it looks (student page)</strong></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <?php if (!empty($course['thumbnail'])): ?>
                                    <img class="img-fluid rounded" src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <?php else: ?>
                                    <div class="bg-primary rounded d-flex align-items-center justify-content-center" style="height:220px;">
                                        <i class="fas fa-book fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7">
                                <h4 class="mb-1"><?php echo htmlspecialchars($course['title']); ?></h4>
                                <div class="text-muted mb-2"><?php echo htmlspecialchars($course['instructor_name'] ?? ''); ?></div>
                                <div class="mb-2">
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($course['difficulty_level'])); ?></span>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($course['category_name'] ?? ''); ?></span>
                                    <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($course['status']); ?></span>
                                </div>
                                <div class="h5 text-success mb-2">Rs<?php echo number_format((float)$course['price'], 2); ?></div>
                                <p class="mb-0"><?php echo htmlspecialchars(substr($course['description'], 0, 160)); ?>...</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="lessonModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lessonModalTitle">Lesson</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs mb-4" id="lessonTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab">Content</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button" role="tab">Resources</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">Assignments</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">Notes</button>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content" id="lessonTabContent">
                        <!-- Content Tab -->
                        <div class="tab-pane fade show active" id="content" role="tabpanel">
                            <form id="formLesson" enctype="multipart/form-data">
                                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                                <input type="hidden" name="lesson_id" id="lesson_id" value="">

                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input class="form-control" name="title" id="lesson_title" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Type</label>
                                        <select class="form-select" name="lesson_type" id="lesson_type">
                                            <option value="video">Video</option>
                                            <option value="text">Text</option>
                                            <option value="quiz">Quiz</option>
                                            <option value="assignment">Assignment</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Duration (minutes)</label>
                                        <input type="number" class="form-control" name="duration_minutes" id="lesson_duration" min="0">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Free preview</label>
                                        <select class="form-select" name="is_free" id="lesson_is_free">
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Video Content</label>
                                    <select class="form-select mb-2" name="video_source" id="video_source">
                                        <option value="none">No Video</option>
                                        <option value="upload">Upload Video File</option>
                                        <option value="google_drive">Google Drive URL</option>
                                        <option value="external_url">External Video URL</option>
                                    </select>
                                </div>

                                <!-- Video Upload Section -->
                                <div id="videoUploadSection" style="display: none;" class="mb-3">
                                    <label class="form-label">Upload Video File</label>
                                    <div class="video-upload-area" id="videoUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center;">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="mb-1">Drag and drop video file here or click to browse</p>
                                        <small class="text-muted">MP4, WebM, OGG (Max: 500MB)</small>
                                        <input type="file" name="video_file" id="video_file" accept="video/*" style="display: none;">
                                    </div>
                                    <div id="videoPreview" style="display: none; margin-top: 10px;">
                                        <video controls style="width: 100%; max-height: 200px;"></video>
                                        <div class="mt-2">
                                            <span id="videoFileName" class="text-muted"></span>
                                            <span id="videoFileSize" class="text-muted ms-3"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Google Drive Section -->
                                <div id="googleDriveSection" style="display: none;" class="mb-3">
                                    <label class="form-label">Google Drive Video URL</label>
                                    <input type="url" class="form-control" name="google_drive_url" id="google_drive_url" 
                                           placeholder="https://drive.google.com/file/d/FILE_ID/view">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Share your Google Drive video with "Anyone with the link" access
                                    </div>
                                </div>

                                <!-- External URL Section -->
                                <div id="externalUrlSection" style="display: none;" class="mb-3">
                                    <label class="form-label">External Video URL</label>
                                    <input type="url" class="form-control" name="video_url" id="lesson_video_url" 
                                           placeholder="https://example.com/video.mp4">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Direct link to video file or streaming URL
                                    </div>
                                </div>

                                <!-- Video Quality Settings -->
                                <div id="videoSettingsSection" style="display: none;" class="mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Video Quality</label>
                                            <select class="form-select" name="video_quality">
                                                <option value="360p">360p</option>
                                                <option value="480p">480p</option>
                                                <option value="720p" selected>720p</option>
                                                <option value="1080p">1080p</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Downloadable</label>
                                            <select class="form-select" name="is_downloadable">
                                                <option value="0">No</option>
                                                <option value="1">Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Content</label>
                                    <textarea class="form-control" name="content" id="lesson_content" rows="6"></textarea>
                                </div>
                            </form>
                        </div>

                        <!-- Resources Tab -->
                        <div class="tab-pane fade" id="resources" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Lesson Resources</h6>
                                <button class="btn btn-sm btn-primary" id="btnAddResource">
                                    <i class="fas fa-plus me-1"></i>Add Resource
                                </button>
                            </div>
                            
                            <div id="resourcesList" class="mb-3">
                                <div class="text-muted text-center py-3">
                                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                                    <p>No resources added yet</p>
                                </div>
                            </div>

                            <button class="btn btn-outline-secondary btn-sm" id="btnSaveResourceOrder" style="display: none;">
                                <i class="fas fa-sort me-1"></i>Save Order
                            </button>
                        </div>

                        <!-- Assignments Tab -->
                        <div class="tab-pane fade" id="assignments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Assignments & Tasks</h6>
                                <button class="btn btn-sm btn-primary" id="btnAddAssignment">
                                    <i class="fas fa-plus me-1"></i>Add Assignment
                                </button>
                            </div>
                            
                            <div id="assignmentsList" class="mb-3">
                                <div class="text-muted text-center py-3">
                                    <i class="fas fa-tasks fa-2x mb-2"></i>
                                    <p>No assignments added yet</p>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Tab -->
                        <div class="tab-pane fade" id="notes" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Instructor Notes</label>
                                <textarea class="form-control" id="lessonInstructorNotes" rows="8" 
                                          placeholder="Add teaching notes, key points, reminders for this lesson..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Study Materials</label>
                                <textarea class="form-control" id="lessonStudyMaterials" rows="6" 
                                          placeholder="Additional study materials, reading lists, references..."></textarea>
                            </div>

                            <button class="btn btn-primary btn-sm" id="btnSaveNotes">
                                <i class="fas fa-save me-1"></i>Save Notes
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" id="btnDeleteLesson" style="display:none;"><i class="fas fa-trash me-2"></i>Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btnSaveLesson"><i class="fas fa-save me-2"></i>Save Lesson</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Resource Modal -->
    <div class="modal fade" id="resourceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resourceModalTitle">Add Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formResource" enctype="multipart/form-data">
                        <input type="hidden" name="lesson_id" id="resource_lesson_id" value="">
                        <input type="hidden" name="resource_id" id="resource_id" value="">

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input class="form-control" name="title" id="resource_title" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="resource_description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Resource Type</label>
                            <select class="form-select" name="resource_type" id="resource_type">
                                <option value="document">Document</option>
                                <option value="presentation">Presentation</option>
                                <option value="video">Video</option>
                                <option value="audio">Audio</option>
                                <option value="image">Image</option>
                                <option value="link">External Link</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- File Upload Section -->
                        <div id="resourceFileSection" class="mb-3">
                            <label class="form-label">Upload File</label>
                            <div class="resource-upload-area" id="resourceUploadArea" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center;">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-1">Drag and drop file here or click to browse</p>
                                <small class="text-muted">PDF, DOC, PPT, Images, etc. (Max: 50MB)</small>
                                <input type="file" name="resource_file" id="resource_file" style="display: none;">
                            </div>
                            <div id="resourceFilePreview" style="display: none; margin-top: 10px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file fa-2x text-muted me-3"></i>
                                    <div>
                                        <div id="resourceFileName" class="fw-semibold"></div>
                                        <div id="resourceFileSize" class="text-muted small"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- External URL Section -->
                        <div id="resourceUrlSection" style="display: none;" class="mb-3">
                            <label class="form-label">External URL</label>
                            <input type="url" class="form-control" name="external_url" id="resource_external_url" 
                                   placeholder="https://example.com/resource">
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Direct link to external resource
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Downloadable</label>
                                <select class="form-select" name="is_downloadable" id="resource_is_downloadable">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" id="resource_sort_order" min="0" value="0">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" id="btnDeleteResource" style="display:none;"><i class="fas fa-trash me-2"></i>Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveResource"><i class="fas fa-save me-2"></i>Save Resource</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div class="modal fade" id="assignmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignmentModalTitle">Add Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAssignment">
                        <input type="hidden" name="lesson_id" id="assignment_lesson_id" value="">
                        <input type="hidden" name="assignment_id" id="assignment_id" value="">

                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Title</label>
                                <input class="form-control" name="title" id="assignment_title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="assignment_type" id="assignment_type">
                                    <option value="text_submission">Text Submission</option>
                                    <option value="file_upload">File Upload</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="external">External</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="assignment_description" rows="3" 
                                      placeholder="Brief description of the assignment..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Instructions</label>
                            <textarea class="form-control" name="instructions" id="assignment_instructions" rows="6" 
                                      placeholder="Detailed instructions for students..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="datetime-local" class="form-control" name="due_date" id="assignment_due_date">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max Points</label>
                                <input type="number" class="form-control" name="max_points" id="assignment_points_possible" 
                                       min="0" value="100">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Published</label>
                                <select class="form-select" name="is_published" id="assignment_is_published">
                                    <option value="0">Draft</option>
                                    <option value="1">Published</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="assignment_sort_order" 
                                   min="0" value="0">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" id="btnDeleteAssignment" style="display:none;">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveAssignment">
                        <i class="fas fa-save me-2"></i>Save Assignment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const COURSE_ID = <?php echo (int)$courseId; ?>;
        const initialMeta = <?php echo json_encode(['what_you_learn' => $whatYouLearn, 'requirements' => $requirements, 'target_audience' => $targetAudience, 'faqs' => $faqs], JSON_UNESCAPED_UNICODE); ?>;

        function el(tag, attrs = {}, children = []) {
            const node = document.createElement(tag);
            Object.entries(attrs).forEach(([k, v]) => {
                if (k === 'class') node.className = v;
                else if (k === 'text') node.textContent = v;
                else node.setAttribute(k, v);
            });
            children.forEach(c => node.appendChild(c));
            return node;
        }

        function renderList(containerId, items, placeholder) {
            const root = document.getElementById(containerId);
            root.innerHTML = '';
            items.forEach((value, idx) => {
                const input = el('input', { class: 'form-control', value: value ?? '', placeholder });
                input.addEventListener('input', () => items[idx] = input.value);
                const btn = el('button', { class: 'btn btn-outline-danger', type: 'button' }, [el('i', { class: 'fas fa-times' })]);
                btn.addEventListener('click', () => { items.splice(idx, 1); renderAllLists(); });
                root.appendChild(el('div', { class: 'list-editor-item' }, [input, btn]));
            });
        }

        function renderFaq(containerId, items) {
            const root = document.getElementById(containerId);
            root.innerHTML = '';
            items.forEach((faq, idx) => {
                const q = el('input', { class: 'form-control', value: faq?.q ?? '', placeholder: 'Question' });
                const a = el('input', { class: 'form-control', value: faq?.a ?? '', placeholder: 'Answer' });
                q.addEventListener('input', () => { items[idx] = items[idx] || {}; items[idx].q = q.value; });
                a.addEventListener('input', () => { items[idx] = items[idx] || {}; items[idx].a = a.value; });
                const btn = el('button', { class: 'btn btn-outline-danger', type: 'button' }, [el('i', { class: 'fas fa-times' })]);
                btn.addEventListener('click', () => { items.splice(idx, 1); renderAllLists(); });
                root.appendChild(el('div', { class: 'list-editor-item' }, [q, a, btn]));
            });
        }

        const state = {
            what_you_learn: Array.isArray(initialMeta.what_you_learn) ? [...initialMeta.what_you_learn] : [],
            requirements: Array.isArray(initialMeta.requirements) ? [...initialMeta.requirements] : [],
            target_audience: Array.isArray(initialMeta.target_audience) ? [...initialMeta.target_audience] : [],
            faqs: Array.isArray(initialMeta.faqs) ? [...initialMeta.faqs] : [],
            lessons: [],
            currentLessonId: null,
            resources: [],
            assignments: [],
            notes: null
        };

        function renderAllLists() {
            renderList('listWhatYouLearn', state.what_you_learn, 'Learning outcome');
            renderList('listRequirements', state.requirements, 'Requirement');
            renderList('listAudience', state.target_audience, 'Audience');
            renderFaq('listFaq', state.faqs);
        }

        async function api(url, options = {}) {
            const res = await fetch(url, { credentials: 'same-origin', ...options });
            const data = await res.json();
            if (!data.success) {
                throw new Error(data.message || 'Request failed');
            }
            return data;
        }

        async function loadLessons() {
            const data = await api('../api/admin_course_lessons.php?course_id=' + COURSE_ID);
            state.lessons = data.lessons || [];
            
            // Load resource and assignment counts for each lesson
            for (let lesson of state.lessons) {
                try {
                    const [resourceData, assignmentData] = await Promise.all([
                        api('../api/lesson_resources.php?lesson_id=' + lesson.id),
                        api('../api/lesson_assignments.php?lesson_id=' + lesson.id)
                    ]);
                    lesson.resource_count = (resourceData.resources || []).length;
                    lesson.assignment_count = (assignmentData.assignments || []).length;
                } catch (e) {
                    lesson.resource_count = 0;
                    lesson.assignment_count = 0;
                }
            }
            
            renderLessons();
        }

        function lessonTypeBadge(type) {
            if (type === 'video') return 'primary';
            if (type === 'quiz') return 'warning';
            return 'secondary';
        }

        function renderLessons() {
            const root = document.getElementById('lessonsList');
            root.innerHTML = '';

            state.lessons.forEach((l) => {
                const row = el('div', { class: 'lesson-row', draggable: 'true', 'data-id': l.id });
                const left = el('div', { class: 'd-flex align-items-start gap-3' });
                const handle = el('div', { class: 'drag-handle pt-1 text-muted' }, [el('i', { class: 'fas fa-grip-vertical' })]);
                const meta = el('div', { class: 'flex-grow-1' });
                meta.appendChild(el('div', { class: 'fw-semibold' , text: l.title }));
                
                const details = el('div', { class: 'small text-muted' });
                details.appendChild(el('span', { text: `Type: ${l.lesson_type} • Duration: ${l.duration_minutes}m • Order: ${l.lesson_order}` }));
                
                // Show resource and assignment counts
                const badges = [];
                if (l.resource_count > 0) {
                    badges.push(`${l.resource_count} resource${l.resource_count > 1 ? 's' : ''}`);
                }
                if (l.assignment_count > 0) {
                    badges.push(`${l.assignment_count} assignment${l.assignment_count > 1 ? 's' : ''}`);
                }
                
                if (badges.length > 0) {
                    details.appendChild(el('span', { class: 'badge bg-info ms-2', text: badges.join(' • ') }));
                }
                
                meta.appendChild(details);

                const actions = el('div', { class: 'd-flex gap-2' });
                actions.appendChild(el('span', { class: 'badge bg-' + lessonTypeBadge(l.lesson_type), text: l.lesson_type }));
                
                // Show resource and assignment icons
                if (l.resource_count > 0) {
                    actions.appendChild(el('span', { class: 'badge bg-light text-dark', text: `${l.resource_count} 📁` }));
                }
                if (l.assignment_count > 0) {
                    actions.appendChild(el('span', { class: 'badge bg-light text-dark', text: `${l.assignment_count} 📝` }));
                }
                
                const btnEdit = el('button', { class: 'btn btn-sm btn-outline-primary', type: 'button' }, [el('i', { class: 'fas fa-pen' })]);
                btnEdit.addEventListener('click', () => openLessonModal(l));
                actions.appendChild(btnEdit);

                left.appendChild(handle);
                left.appendChild(meta);
                row.appendChild(left);
                row.appendChild(actions);

                row.addEventListener('dragstart', () => row.classList.add('dragging'));
                row.addEventListener('dragend', () => row.classList.remove('dragging'));
                root.appendChild(row);
            });

            root.addEventListener('dragover', (e) => {
                e.preventDefault();
                const dragging = root.querySelector('.dragging');
                if (!dragging) return;
                const afterElement = getDragAfterElement(root, e.clientY);
                if (afterElement == null) {
                    root.appendChild(dragging);
                } else {
                    root.insertBefore(dragging, afterElement);
                }
            });
        }

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.lesson-row:not(.dragging)')];
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        const lessonModal = new bootstrap.Modal(document.getElementById('lessonModal'));

        function openLessonModal(lesson) {
            document.getElementById('lessonModalTitle').textContent = lesson ? 'Edit Lesson' : 'New Lesson';
            document.getElementById('lesson_id').value = lesson?.id || '';
            document.getElementById('lesson_title').value = lesson?.title || '';
            document.getElementById('lesson_type').value = lesson?.lesson_type || 'text';
            document.getElementById('lesson_duration').value = lesson?.duration_minutes || 0;
            document.getElementById('lesson_is_free').value = (lesson?.is_free ?? 0) ? '1' : '0';
            document.getElementById('lesson_video_url').value = lesson?.video_url || '';
            document.getElementById('lesson_content').value = lesson?.content || '';

            // Set current lesson ID for resource management
            state.currentLessonId = lesson?.id || null;

            // Load resources, assignments, and notes for this lesson
            loadResources(state.currentLessonId);
            loadAssignments(state.currentLessonId);
            loadNotes(state.currentLessonId);

            document.getElementById('btnDeleteLesson').style.display = lesson ? '' : 'none';
            lessonModal.show();
        }

        async function saveMeta() {
            const meta = {
                what_you_learn: state.what_you_learn.filter(x => (x || '').trim() !== ''),
                requirements: state.requirements.filter(x => (x || '').trim() !== ''),
                target_audience: state.target_audience.filter(x => (x || '').trim() !== ''),
                faqs: state.faqs.filter(f => f && (f.q || '').trim() !== '' && (f.a || '').trim() !== '')
            };

            await api('../api/admin_course_meta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ course_id: COURSE_ID, meta })
            });
        }

        async function saveBasics() {
            const form = document.getElementById('formBasics');
            const fd = new FormData(form);
            fd.append('action', 'update_course');
            fd.append('course_id', COURSE_ID);

            try {
                const res = await fetch('../instructor/courses.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await res.json();
                console.log('Save basics response:', data);
                
                if (!res.ok) {
                    throw new Error('Failed to save: ' + res.status);
                }
                
                if (data.success) {
                    alert('Course saved successfully!');
                    return true;
                } else {
                    throw new Error(data.message || 'Failed to save course');
                }
            } catch (e) {
                console.error('Save basics error:', e);
                alert('Error: ' + e.message);
                throw e;
            }
        }

        async function saveLesson() {
            const lessonId = document.getElementById('lesson_id').value;
            const payload = {
                action: lessonId ? 'update' : 'create',
                course_id: COURSE_ID,
                lesson_id: lessonId ? parseInt(lessonId, 10) : undefined,
                title: document.getElementById('lesson_title').value,
                lesson_type: document.getElementById('lesson_type').value,
                duration_minutes: parseInt(document.getElementById('lesson_duration').value || '0', 10),
                is_free: parseInt(document.getElementById('lesson_is_free').value || '0', 10),
                video_url: document.getElementById('lesson_video_url').value,
                content: document.getElementById('lesson_content').value
            };

            await api('../api/admin_course_lessons.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            lessonModal.hide();
            await loadLessons();
        }

        async function deleteLesson() {
            const lessonId = parseInt(document.getElementById('lesson_id').value || '0', 10);
            if (!lessonId) return;

            await api('../api/admin_course_lessons.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', lesson_id: lessonId })
            });

            lessonModal.hide();
            await loadLessons();
        }

        async function saveOrder() {
            const ids = [...document.querySelectorAll('#lessonsList .lesson-row')].map(el => parseInt(el.getAttribute('data-id'), 10));
            await api('../api/admin_course_lessons.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reorder', course_id: COURSE_ID, order: ids })
            });
            await loadLessons();
        }

        // Resource Management Functions
        const resourceModal = new bootstrap.Modal(document.getElementById('resourceModal'));

        function getResourceIcon(type) {
            const icons = {
                'document': 'fa-file-alt',
                'presentation': 'fa-file-powerpoint',
                'video': 'fa-video',
                'audio': 'fa-file-audio',
                'image': 'fa-file-image',
                'link': 'fa-link',
                'other': 'fa-file'
            };
            return icons[type] || 'fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        async function loadResources(lessonId) {
            if (!lessonId) {
                state.resources = [];
                renderResources();
                return;
            }

            try {
                const data = await api('../api/lesson_resources.php?lesson_id=' + lessonId);
                state.resources = data.resources || [];
                renderResources();
            } catch (e) {
                console.error('Failed to load resources:', e);
                state.resources = [];
                renderResources();
            }
        }

        function renderResources() {
            const root = document.getElementById('resourcesList');
            root.innerHTML = '';

            if (state.resources.length === 0) {
                root.innerHTML = `
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-folder-open fa-2x mb-2"></i>
                        <p>No resources added yet</p>
                    </div>
                `;
                document.getElementById('btnSaveResourceOrder').style.display = 'none';
                return;
            }

            state.resources.forEach((resource, idx) => {
                const row = el('div', { 
                    class: 'resource-row border rounded p-3 mb-2', 
                    draggable: 'true', 
                    'data-id': resource.id 
                });

                const left = el('div', { class: 'd-flex align-items-start gap-3' });
                const handle = el('div', { class: 'drag-handle pt-1 text-muted' }, [el('i', { class: 'fas fa-grip-vertical' })]);
                
                const icon = el('i', { 
                    class: `fas ${getResourceIcon(resource.resource_type)} fa-2x text-muted` 
                });

                const meta = el('div', { class: 'flex-grow-1' });
                meta.appendChild(el('div', { class: 'fw-semibold', text: resource.title }));
                
                if (resource.description) {
                    meta.appendChild(el('div', { class: 'small text-muted', text: resource.description }));
                }

                const details = el('div', { class: 'small text-muted' });
                const typeText = resource.resource_type.charAt(0).toUpperCase() + resource.resource_type.slice(1);
                details.appendChild(el('span', { text: `${typeText}` }));
                
                if (resource.file_size) {
                    details.appendChild(el('span', { text: ` • ${formatFileSize(resource.file_size)}` }));
                }
                
                if (resource.is_downloadable) {
                    details.appendChild(el('span', { class: 'badge bg-success ms-2', text: 'Downloadable' }));
                }
                
                meta.appendChild(details);

                const actions = el('div', { class: 'd-flex gap-2' });
                const btnEdit = el('button', { 
                    class: 'btn btn-sm btn-outline-primary', 
                    type: 'button' 
                }, [el('i', { class: 'fas fa-pen' })]);
                btnEdit.addEventListener('click', () => openResourceModal(resource));
                actions.appendChild(btnEdit);

                left.appendChild(handle);
                left.appendChild(icon);
                left.appendChild(meta);
                row.appendChild(left);
                row.appendChild(actions);

                row.addEventListener('dragstart', () => row.classList.add('dragging'));
                row.addEventListener('dragend', () => row.classList.remove('dragging'));
                root.appendChild(row);
            });

            document.getElementById('btnSaveResourceOrder').style.display = 'block';

            // Add drag and drop functionality
            root.addEventListener('dragover', (e) => {
                e.preventDefault();
                const dragging = root.querySelector('.dragging');
                if (!dragging) return;
                const afterElement = getDragAfterElement(root, e.clientY);
                if (afterElement == null) {
                    root.appendChild(dragging);
                } else {
                    root.insertBefore(dragging, afterElement);
                }
            });
        }

        function openResourceModal(resource) {
            document.getElementById('resourceModalTitle').textContent = resource ? 'Edit Resource' : 'Add Resource';
            document.getElementById('resource_lesson_id').value = state.currentLessonId;
            document.getElementById('resource_id').value = resource?.id || '';
            document.getElementById('resource_title').value = resource?.title || '';
            document.getElementById('resource_description').value = resource?.description || '';
            document.getElementById('resource_type').value = resource?.resource_type || 'document';
            document.getElementById('resource_external_url').value = resource?.external_url || '';
            document.getElementById('resource_is_downloadable').value = resource?.is_downloadable ? '1' : '0';
            document.getElementById('resource_sort_order').value = resource?.sort_order || 0;

            // Show/hide appropriate sections
            const resourceType = document.getElementById('resource_type').value;
            document.getElementById('resourceFileSection').style.display = resourceType === 'link' ? 'none' : 'block';
            document.getElementById('resourceUrlSection').style.display = resourceType === 'link' ? 'block' : 'none';

            // Reset file preview
            document.getElementById('resourceFilePreview').style.display = 'none';
            document.getElementById('resource_file').value = '';

            document.getElementById('btnDeleteResource').style.display = resource ? '' : 'none';
            resourceModal.show();
        }

        async function saveResource() {
            const form = document.getElementById('formResource');
            const formData = new FormData(form);
            const resourceId = document.getElementById('resource_id').value;

            if (resourceId) {
                formData.append('action', 'update');
                formData.append('resource_id', resourceId);
            } else {
                formData.append('action', 'create');
            }

            try {
                const response = await fetch('../api/lesson_resources.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save resource');
                }

                resourceModal.hide();
                await loadResources(state.currentLessonId);
            } catch (e) {
                console.error('Save resource error:', e);
                alert('Error: ' + e.message);
            }
        }

        async function deleteResource() {
            const resourceId = document.getElementById('resource_id').value;
            if (!resourceId) return;

            if (!confirm('Delete this resource?')) return;

            try {
                await api('../api/lesson_resources.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'delete', 
                        resource_id: parseInt(resourceId, 10) 
                    })
                });

                resourceModal.hide();
                await loadResources(state.currentLessonId);
            } catch (e) {
                console.error('Delete resource error:', e);
                alert('Error: ' + e.message);
            }
        }

        async function saveResourceOrder() {
            const ids = [...document.querySelectorAll('#resourcesList .resource-row')].map(el => parseInt(el.getAttribute('data-id'), 10));
            
            try {
                await api('../api/lesson_resources.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'reorder', 
                        lesson_id: state.currentLessonId, 
                        order: ids 
                    })
                });
                
                await loadResources(state.currentLessonId);
                alert('Resource order saved');
            } catch (e) {
                console.error('Save resource order error:', e);
                alert('Error: ' + e.message);
            }
        }

        // Assignment Management Functions
        const assignmentModal = new bootstrap.Modal(document.getElementById('assignmentModal'));

        function getAssignmentIcon(type) {
            const icons = {
                'assignment': 'fa-tasks',
                'quiz': 'fa-question-circle',
                'project': 'fa-project-diagram',
                'discussion': 'fa-comments',
                'exam': 'fa-clipboard-list'
            };
            return icons[type] || 'fa-tasks';
        }

        function getAssignmentBadge(type) {
            const badges = {
                'assignment': 'primary',
                'quiz': 'warning',
                'project': 'info',
                'discussion': 'success',
                'exam': 'danger'
            };
            return badges[type] || 'primary';
        }

        async function loadAssignments(lessonId) {
            if (!lessonId) {
                state.assignments = [];
                renderAssignments();
                return;
            }

            try {
                const data = await api('../api/lesson_assignments.php?lesson_id=' + lessonId);
                state.assignments = data.assignments || [];
                renderAssignments();
            } catch (e) {
                console.error('Failed to load assignments:', e);
                state.assignments = [];
                renderAssignments();
            }
        }

        function renderAssignments() {
            const root = document.getElementById('assignmentsList');
            root.innerHTML = '';

            if (state.assignments.length === 0) {
                root.innerHTML = `
                    <div class="text-muted text-center py-3">
                        <i class="fas fa-tasks fa-2x mb-2"></i>
                        <p>No assignments added yet</p>
                    </div>
                `;
                return;
            }

            state.assignments.forEach((assignment) => {
                const row = el('div', { 
                    class: 'assignment-row border rounded p-3 mb-2', 
                    draggable: 'true', 
                    'data-id': assignment.id 
                });

                const left = el('div', { class: 'd-flex align-items-start gap-3' });
                const handle = el('div', { class: 'drag-handle pt-1 text-muted' }, [el('i', { class: 'fas fa-grip-vertical' })]);
                
                const icon = el('i', { 
                    class: `fas ${getAssignmentIcon(assignment.assignment_type)} fa-2x text-muted` 
                });

                const meta = el('div', { class: 'flex-grow-1' });
                meta.appendChild(el('div', { class: 'fw-semibold', text: assignment.title }));
                
                if (assignment.description) {
                    meta.appendChild(el('div', { class: 'small text-muted', text: assignment.description }));
                }

                const details = el('div', { class: 'small text-muted' });
                const typeText = assignment.assignment_type.replace('_', ' ').charAt(0).toUpperCase() + assignment.assignment_type.replace('_', ' ').slice(1);
                details.appendChild(el('span', { text: `${typeText}` }));
                
                if (assignment.max_points) {
                    details.appendChild(el('span', { text: ` • ${assignment.max_points} points` }));
                }
                
                if (assignment.due_date) {
                    const dueDate = new Date(assignment.due_date).toLocaleDateString();
                    details.appendChild(el('span', { text: ` • Due: ${dueDate}` }));
                }
                
                if (assignment.is_published) {
                    details.appendChild(el('span', { class: 'badge bg-success ms-2', text: 'Published' }));
                } else {
                    details.appendChild(el('span', { class: 'badge bg-secondary ms-2', text: 'Draft' }));
                }
                
                meta.appendChild(details);

                const actions = el('div', { class: 'd-flex gap-2' });
                actions.appendChild(el('span', { 
                    class: 'badge bg-' + getAssignmentBadge(assignment.assignment_type), 
                    text: assignment.assignment_type 
                }));
                
                const btnEdit = el('button', { 
                    class: 'btn btn-sm btn-outline-primary', 
                    type: 'button' 
                }, [el('i', { class: 'fas fa-pen' })]);
                btnEdit.addEventListener('click', () => openAssignmentModal(assignment));
                actions.appendChild(btnEdit);

                left.appendChild(handle);
                left.appendChild(icon);
                left.appendChild(meta);
                row.appendChild(left);
                row.appendChild(actions);

                row.addEventListener('dragstart', () => row.classList.add('dragging'));
                row.addEventListener('dragend', () => row.classList.remove('dragging'));
                root.appendChild(row);
            });
        }

        function openAssignmentModal(assignment) {
            document.getElementById('assignmentModalTitle').textContent = assignment ? 'Edit Assignment' : 'Add Assignment';
            document.getElementById('assignment_lesson_id').value = state.currentLessonId;
            document.getElementById('assignment_id').value = assignment?.id || '';
            document.getElementById('assignment_title').value = assignment?.title || '';
            document.getElementById('assignment_description').value = assignment?.description || '';
            document.getElementById('assignment_instructions').value = assignment?.instructions || '';
            document.getElementById('assignment_type').value = assignment?.assignment_type || 'text_submission';
            document.getElementById('assignment_due_date').value = assignment?.due_date || '';
            document.getElementById('assignment_points_possible').value = assignment?.max_points || 100;
            document.getElementById('assignment_is_published').value = assignment?.is_published ? '1' : '0';
            document.getElementById('assignment_sort_order').value = assignment?.sort_order || 0;

            document.getElementById('btnDeleteAssignment').style.display = assignment ? '' : 'none';
            assignmentModal.show();
        }

        async function saveAssignment() {
            const form = document.getElementById('formAssignment');
            const formData = new FormData(form);
            const assignmentId = document.getElementById('assignment_id').value;

            if (assignmentId) {
                formData.append('action', 'update');
                formData.append('assignment_id', assignmentId);
            } else {
                formData.append('action', 'create');
            }

            try {
                const response = await fetch('../api/lesson_assignments.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save assignment');
                }

                assignmentModal.hide();
                await loadAssignments(state.currentLessonId);
            } catch (e) {
                console.error('Save assignment error:', e);
                alert('Error: ' + e.message);
            }
        }

        async function deleteAssignment() {
            const assignmentId = document.getElementById('assignment_id').value;
            if (!assignmentId) return;

            if (!confirm('Delete this assignment?')) return;

            try {
                await api('../api/lesson_assignments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'delete', 
                        assignment_id: parseInt(assignmentId, 10) 
                    })
                });

                assignmentModal.hide();
                await loadAssignments(state.currentLessonId);
            } catch (e) {
                console.error('Delete assignment error:', e);
                alert('Error: ' + e.message);
            }
        }

        // Notes Management Functions
        async function loadNotes(lessonId) {
            if (!lessonId) {
                state.notes = null;
                updateNotesForm();
                return;
            }

            try {
                const data = await api('../api/lesson_notes.php?lesson_id=' + lessonId);
                state.notes = data.notes || null;
                updateNotesForm();
            } catch (e) {
                console.error('Failed to load notes:', e);
                state.notes = null;
                updateNotesForm();
            }
        }

        function updateNotesForm() {
            document.getElementById('lessonInstructorNotes').value = state.notes?.content || '';
            document.getElementById('lessonStudyMaterials').value = state.notes?.title || 'Instructor Notes';
        }

        async function saveNotes() {
            if (!state.currentLessonId) return;

            try {
                await api('../api/lesson_notes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save',
                        lesson_id: state.currentLessonId,
                        title: 'Instructor Notes',
                        content: document.getElementById('lessonInstructorNotes').value
                    })
                });

                alert('Notes saved successfully');
            } catch (e) {
                console.error('Save notes error:', e);
                alert('Error: ' + e.message);
            }
        }

        document.getElementById('btnAddLearn').addEventListener('click', () => { state.what_you_learn.push(''); renderAllLists(); });
        document.getElementById('btnAddReq').addEventListener('click', () => { state.requirements.push(''); renderAllLists(); });
        document.getElementById('btnAddAud').addEventListener('click', () => { state.target_audience.push(''); renderAllLists(); });
        document.getElementById('btnAddFaq').addEventListener('click', () => { state.faqs.push({ q: '', a: '' }); renderAllLists(); });

        // Resource event listeners
        document.getElementById('btnAddResource').addEventListener('click', () => openResourceModal(null));
        document.getElementById('btnSaveResource').addEventListener('click', saveResource);
        document.getElementById('btnDeleteResource').addEventListener('click', deleteResource);
        document.getElementById('btnSaveResourceOrder').addEventListener('click', saveResourceOrder);

        // Assignment event listeners
        document.getElementById('btnAddAssignment').addEventListener('click', () => openAssignmentModal(null));
        document.getElementById('btnSaveAssignment').addEventListener('click', saveAssignment);
        document.getElementById('btnDeleteAssignment').addEventListener('click', deleteAssignment);

        // Notes event listeners
        document.getElementById('btnSaveNotes').addEventListener('click', saveNotes);

        // Resource type change handler
        document.getElementById('resource_type').addEventListener('change', (e) => {
            const resourceType = e.target.value;
            document.getElementById('resourceFileSection').style.display = resourceType === 'link' ? 'none' : 'block';
            document.getElementById('resourceUrlSection').style.display = resourceType === 'link' ? 'block' : 'none';
        });

        // Resource file upload handlers
        const resourceUploadArea = document.getElementById('resourceUploadArea');
        const resourceFileInput = document.getElementById('resource_file');

        if (resourceUploadArea && resourceFileInput) {
            resourceUploadArea.addEventListener('click', () => resourceFileInput.click());
            
            resourceUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                resourceUploadArea.style.borderColor = '#007bff';
                resourceUploadArea.style.backgroundColor = '#f8f9fa';
            });

            resourceUploadArea.addEventListener('dragleave', () => {
                resourceUploadArea.style.borderColor = '#dee2e6';
                resourceUploadArea.style.backgroundColor = '';
            });

            resourceUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                resourceUploadArea.style.borderColor = '#dee2e6';
                resourceUploadArea.style.backgroundColor = '';
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleResourceFile(files[0]);
                }
            });

            resourceFileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleResourceFile(e.target.files[0]);
                }
            });

            function handleResourceFile(file) {
                const maxSize = 50 * 1024 * 1024; // 50MB
                if (file.size > maxSize) {
                    alert('File size must be less than 50MB.');
                    return;
                }

                document.getElementById('resourceFileName').textContent = file.name;
                document.getElementById('resourceFileSize').textContent = formatFileSize(file.size);
                document.getElementById('resourceFilePreview').style.display = 'block';
                resourceUploadArea.style.display = 'none';
            }
        }

        document.getElementById('btnSaveMeta').addEventListener('click', async () => {
            try {
                await saveMeta();
                alert('Saved details');
            } catch (e) {
                alert(e.message);
            }
        });

        document.getElementById('btnSaveBasics').addEventListener('click', async () => {
            try {
                await saveBasics();
                alert('Saved basics');
            } catch (e) {
                alert(e.message);
            }
        });

        document.getElementById('btnQuickSave').addEventListener('click', async () => {
            try {
                await saveBasics();
                await saveMeta();
                alert('Saved all');
            } catch (e) {
                alert(e.message);
            }
        });

        document.getElementById('btnNewLesson').addEventListener('click', () => openLessonModal(null));
        document.getElementById('btnSaveLesson').addEventListener('click', async () => {
            try {
                await saveLesson();
            } catch (e) {
                alert(e.message);
            }
        });
        document.getElementById('btnDeleteLesson').addEventListener('click', async () => {
            if (!confirm('Delete this lesson?')) return;
            try {
                await deleteLesson();
            } catch (e) {
                alert(e.message);
            }
        });
        document.getElementById('btnSaveOrder').addEventListener('click', async () => {
            try {
                await saveOrder();
                alert('Order saved');
            } catch (e) {
                alert(e.message);
            }
        });

        renderAllLists();
        loadLessons();

        // Video upload functionality
        const videoUploadArea = document.getElementById('videoUploadArea');
        const videoFileInput = document.getElementById('video_file');
        const videoPreview = document.getElementById('videoPreview');
        const videoPreviewElement = videoPreview.querySelector('video');

        if (videoUploadArea && videoFileInput) {
            videoUploadArea.addEventListener('click', () => videoFileInput.click());
            
            videoUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                videoUploadArea.style.borderColor = '#007bff';
                videoUploadArea.style.backgroundColor = '#f8f9fa';
            });

            videoUploadArea.addEventListener('dragleave', () => {
                videoUploadArea.style.borderColor = '#dee2e6';
                videoUploadArea.style.backgroundColor = '';
            });

            videoUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                videoUploadArea.style.borderColor = '#dee2e6';
                videoUploadArea.style.backgroundColor = '';
                
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
        }

        // Video source handling
        const videoSourceSelect = document.getElementById('video_source');
        if (videoSourceSelect) {
            videoSourceSelect.addEventListener('change', (e) => {
                const source = e.target.value;
                document.getElementById('videoUploadSection').style.display = source === 'upload' ? 'block' : 'none';
                document.getElementById('googleDriveSection').style.display = source === 'google_drive' ? 'block' : 'none';
                document.getElementById('externalUrlSection').style.display = source === 'external_url' ? 'block' : 'none';
                document.getElementById('videoSettingsSection').style.display = source !== 'none' ? 'block' : 'none';
            });
        }

        // Lesson type handling
        const lessonTypeSelect = document.getElementById('lesson_type');
        if (lessonTypeSelect) {
            lessonTypeSelect.addEventListener('change', (e) => {
                const isVideoLesson = e.target.value === 'video';
                const videoContentDiv = document.querySelector('[for="video_source"]')?.closest('.mb-3');
                if (videoContentDiv) {
                    videoContentDiv.style.display = isVideoLesson ? 'block' : 'none';
                }
                
                if (!isVideoLesson && videoSourceSelect) {
                    videoSourceSelect.value = 'none';
                    document.getElementById('videoUploadSection').style.display = 'none';
                    document.getElementById('googleDriveSection').style.display = 'none';
                    document.getElementById('externalUrlSection').style.display = 'none';
                    document.getElementById('videoSettingsSection').style.display = 'none';
                }
            });

            // Initialize video content visibility
            const lessonType = lessonTypeSelect.value;
            const videoContentDiv = document.querySelector('[for="video_source"]')?.closest('.mb-3');
            if (videoContentDiv) {
                videoContentDiv.style.display = lessonType === 'video' ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>
