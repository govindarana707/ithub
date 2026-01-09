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
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-graduation-cap me-2"></i>IT HUB</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="courses.php"><i class="fas fa-arrow-left me-1"></i>Back to Courses</a>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger" id="btnDeleteLesson" style="display:none;"><i class="fas fa-trash me-2"></i>Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btnSaveLesson"><i class="fas fa-save me-2"></i>Save Lesson</button>
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
            lessons: []
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
                meta.appendChild(el('div', { class: 'small text-muted', text: `Type: ${l.lesson_type} • Duration: ${l.duration_minutes}m • Order: ${l.lesson_order}` }));

                const actions = el('div', { class: 'd-flex gap-2' });
                actions.appendChild(el('span', { class: 'badge bg-' + lessonTypeBadge(l.lesson_type), text: l.lesson_type }));
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

            const res = await fetch('courses.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });

            if (!res.ok) {
                throw new Error('Failed to save basics');
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

        document.getElementById('btnAddLearn').addEventListener('click', () => { state.what_you_learn.push(''); renderAllLists(); });
        document.getElementById('btnAddReq').addEventListener('click', () => { state.requirements.push(''); renderAllLists(); });
        document.getElementById('btnAddAud').addEventListener('click', () => { state.target_audience.push(''); renderAllLists(); });
        document.getElementById('btnAddFaq').addEventListener('click', () => { state.faqs.push({ q: '', a: '' }); renderAllLists(); });

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
