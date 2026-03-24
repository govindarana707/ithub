<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

$courseId = intval($_GET['course_id'] ?? 0);
$lessonId = intval($_GET['lesson_id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($courseId <= 0) {
    $_SESSION['error_message'] = 'Invalid course';
    header('Location: courses.php');
    exit;
}

// Verify course ownership
require_once '../models/Course.php';
$courseModel = new Course();
$course = $courseModel->getCourseById($courseId);

if (!$course || (getUserRole() === 'instructor' && (int)$course['instructor_id'] !== (int)$_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Access denied';
    header('Location: courses.php');
    exit;
}

// Load existing lesson data if editing
$lesson = null;
if ($lessonId > 0 && $action === 'edit') {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ? AND course_id = ?");
    $stmt->bind_param("ii", $lessonId, $courseId);
    $stmt->execute();
    $lesson = $stmt->get_result()->fetch_assoc();
    
    if (!$lesson) {
        $_SESSION['error_message'] = 'Lesson not found';
        header('Location: course_builder.php?id=' . $courseId);
        exit;
    }
    
    // Decode quiz content if it's a quiz lesson
    $quizQuestions = [];
    if ($lesson && $lesson['lesson_type'] === 'quiz' && !empty($lesson['content'])) {
        $decoded = json_decode($lesson['content'], true);
        if (is_array($decoded)) {
            $quizQuestions = $decoded;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Editor - <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB Instructor
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="courses.php">
                    <i class="fas fa-arrow-left me-1"></i> Back to Courses
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            <?php echo $action === 'new' ? 'Create New Lesson' : 'Edit Lesson'; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../api/save_lesson.php" enctype="multipart/form-data" id="lessonForm">
                            <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                            <?php if ($lessonId > 0): ?>
                                <input type="hidden" name="lesson_id" value="<?php echo $lessonId; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Lesson Title *</label>
                                <input type="text" class="form-control" name="title" required value="<?php echo htmlspecialchars($lesson['title'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Content Type *</label>
                                <select class="form-select" name="content_type" id="contentType" required>
                                    <option value="video" <?php echo (($lesson['lesson_type'] ?? '') === 'video') ? 'selected' : ''; ?>>Video</option>
                                    <option value="text" <?php echo (($lesson['lesson_type'] ?? '') === 'text') ? 'selected' : ''; ?>>Text</option>
                                    <option value="quiz" <?php echo (($lesson['lesson_type'] ?? '') === 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                                    <option value="assignment" <?php echo (($lesson['lesson_type'] ?? '') === 'assignment') ? 'selected' : ''; ?>>Assignment</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Duration (minutes) *</label>
                                <input type="number" class="form-control" name="duration" min="1" required value="<?php echo htmlspecialchars($lesson['duration_minutes'] ?? '10'); ?>">
                            </div>
                            
                            <!-- Video Upload Section -->
                            <div id="videoSection" class="content-section">
                                <?php if (!empty($lesson['video_url'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Video</label>
                                    <div class="border rounded p-2 bg-light mb-2">
                                        <small class="text-muted"><?php echo htmlspecialchars($lesson['video_url']); ?></small>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label class="form-label">Video File</label>
                                    <input type="file" class="form-control" name="video_file" id="videoFile" accept="video/*">
                                    <div class="form-text">Supported formats: MP4, AVI, MOV, WMV (Max: 500MB)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Or Video URL</label>
                                    <input type="url" class="form-control" name="video_url" id="videoUrl" placeholder="https://example.com/video.mp4" value="<?php echo htmlspecialchars($lesson['video_url'] ?? ''); ?>">
                                    <div class="form-text">External video URL (YouTube, Vimeo, etc.)</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Video Thumbnail</label>
                                    <input type="file" class="form-control" name="video_thumbnail" accept="image/*">
                                    <div class="form-text">Optional thumbnail image</div>
                                </div>
                                
                                <div id="videoPreview" class="mb-3" style="display: none;">
                                    <label class="form-label">Video Preview</label>
                                    <div class="border rounded p-3 bg-light">
                                        <video id="previewVideo" controls style="width: 100%; max-height: 300px;"></video>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Text Content Section -->
                            <div id="textSection" class="content-section" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Text Content</label>
                                    <textarea class="form-control" name="text_content" rows="10" placeholder="Enter lesson text content..."><?php echo htmlspecialchars($lesson['content'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Quiz Section -->
                            <div id="quizSection" class="content-section" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Quiz Questions</label>
                                    <div id="quizQuestions">
                                        <?php if (!empty($quizQuestions)): ?>
                                            <?php foreach ($quizQuestions as $index => $q): ?>
                                                <div class="quiz-question border rounded p-3 mb-3" data-index="<?php echo $index; ?>">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="badge bg-primary">Question <?php echo $index + 1; ?></span>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuizQuestion(this)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <div class="mb-2">
                                                        <input type="text" class="form-control" name="quiz_question[]" placeholder="Question <?php echo $index + 1; ?>" value="<?php echo htmlspecialchars($q['question'] ?? ''); ?>">
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control mb-2" name="quiz_option_a[]" placeholder="Option A" value="<?php echo htmlspecialchars($q['options']['a'] ?? ''); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control mb-2" name="quiz_option_b[]" placeholder="Option B" value="<?php echo htmlspecialchars($q['options']['b'] ?? ''); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control mb-2" name="quiz_option_c[]" placeholder="Option C" value="<?php echo htmlspecialchars($q['options']['c'] ?? ''); ?>">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control mb-2" name="quiz_option_d[]" placeholder="Option D" value="<?php echo htmlspecialchars($q['options']['d'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <select class="form-select" name="quiz_correct_answer[]">
                                                            <option value="">Select Correct Answer</option>
                                                            <option value="a" <?php echo (($q['correct_answer'] ?? '') === 'a') ? 'selected' : ''; ?>>A</option>
                                                            <option value="b" <?php echo (($q['correct_answer'] ?? '') === 'b') ? 'selected' : ''; ?>>B</option>
                                                            <option value="c" <?php echo (($q['correct_answer'] ?? '') === 'c') ? 'selected' : ''; ?>>C</option>
                                                            <option value="d" <?php echo (($q['correct_answer'] ?? '') === 'd') ? 'selected' : ''; ?>>D</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="quiz-question border rounded p-3 mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="badge bg-primary">Question 1</span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuizQuestion(this)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="mb-2">
                                                    <input type="text" class="form-control" name="quiz_question[]" placeholder="Question 1">
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control mb-2" name="quiz_option_a[]" placeholder="Option A">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control mb-2" name="quiz_option_b[]" placeholder="Option B">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control mb-2" name="quiz_option_c[]" placeholder="Option C">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <input type="text" class="form-control mb-2" name="quiz_option_d[]" placeholder="Option D">
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <select class="form-select" name="quiz_correct_answer[]">
                                                        <option value="">Select Correct Answer</option>
                                                        <option value="a">A</option>
                                                        <option value="b">B</option>
                                                        <option value="c">C</option>
                                                        <option value="d">D</option>
                                                    </select>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addQuizQuestion()">
                                        <i class="fas fa-plus me-1"></i>Add Question
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Assignment Section -->
                            <div id="assignmentSection" class="content-section" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Assignment Description</label>
                                    <textarea class="form-control" name="assignment_description" rows="6" placeholder="Describe the assignment requirements..."><?php echo htmlspecialchars($lesson['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Assignment Files (Optional)</label>
                                    <input type="file" class="form-control" name="assignment_files[]" multiple>
                                    <div class="form-text">Upload reference files for the assignment</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Due Date (Optional)</label>
                                    <input type="datetime-local" class="form-control" name="assignment_due_date" value="<?php echo htmlspecialchars($lesson['due_date'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <!-- Common Content Section -->
                            <div class="mb-3">
                                <label class="form-label">Additional Notes</label>
                                <textarea class="form-control" name="additional_notes" rows="3" placeholder="Any additional notes or resources..."></textarea>
                            </div>
                            
                            <div class="text-end">
                                <a href="course_builder.php?id=<?php echo $courseId; ?>" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="saveBtn">
                                    <i class="fas fa-save me-1"></i>Save Lesson
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let questionCount = <?php echo !empty($quizQuestions) ? count($quizQuestions) : 1; ?>;

        // Show/hide content sections based on content type
        function showContentSection(contentType) {
            $('.content-section').hide();
            
            switch(contentType) {
                case 'video':
                    $('#videoSection').show();
                    break;
                case 'text':
                    $('#textSection').show();
                    break;
                case 'quiz':
                    $('#quizSection').show();
                    break;
                case 'assignment':
                    $('#assignmentSection').show();
                    break;
            }
        }

        $('#contentType').on('change', function() {
            showContentSection($(this).val());
        });

        // Initialize on page load
        $(document).ready(function() {
            const initialType = $('#contentType').val();
            if (initialType) {
                showContentSection(initialType);
            }
        });

        // Video file preview
        $('#videoFile').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (500MB max)
                if (file.size > 500 * 1024 * 1024) {
                    alert('Video file is too large. Maximum size is 500MB.');
                    $(this).val('');
                    return;
                }

                // Validate file type
                const validTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm'];
                if (!validTypes.includes(file.type)) {
                    alert('Invalid video format. Please use MP4, AVI, MOV, WMV, or WebM.');
                    $(this).val('');
                    return;
                }

                // Show preview
                const url = URL.createObjectURL(file);
                $('#previewVideo').attr('src', url);
                $('#videoPreview').show();
                
                // Clear video URL when file is selected
                $('#videoUrl').val('');
            }
        });

        // Video URL preview
        $('#videoUrl').on('input', function() {
            const url = $(this).val();
            if (url) {
                $('#previewVideo').attr('src', url);
                $('#videoPreview').show();
                
                // Clear file input when URL is entered
                $('#videoFile').val('');
            } else {
                $('#videoPreview').hide();
            }
        });

        // Add quiz question
        function addQuizQuestion() {
            questionCount++;
            const questionHtml = `
                <div class="quiz-question border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6>Question ${questionCount}</h6>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeQuizQuestion(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="mb-2">
                        <input type="text" class="form-control" name="quiz_question[]" placeholder="Question ${questionCount}">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control mb-2" name="quiz_option_a[]" placeholder="Option A">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control mb-2" name="quiz_option_b[]" placeholder="Option B">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control mb-2" name="quiz_option_c[]" placeholder="Option C">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control mb-2" name="quiz_option_d[]" placeholder="Option D">
                        </div>
                    </div>
                    <div class="mb-2">
                        <select class="form-select" name="quiz_correct_answer[]">
                            <option value="">Select Correct Answer</option>
                            <option value="a">A</option>
                            <option value="b">B</option>
                            <option value="c">C</option>
                            <option value="d">D</option>
                        </select>
                    </div>
                </div>
            `;
            $('#quizQuestions').append(questionHtml);
        }

        // Remove quiz question
        function removeQuizQuestion(button) {
            $(button).closest('.quiz-question').remove();
        }

        // Form submission with loading state
        $('#lessonForm').on('submit', function(e) {
            e.preventDefault();
            
            const contentType = $('#contentType').val();
            
            // Validate based on content type
            if (contentType === 'video') {
                const videoFile = $('#videoFile')[0].files[0];
                const videoUrl = $('#videoUrl').val();
                
                if (!videoFile && !videoUrl) {
                    alert('Please upload a video file or provide a video URL.');
                    return;
                }
            } else if (contentType === 'text') {
                const textContent = $('textarea[name="text_content"]').val();
                if (!textContent.trim()) {
                    alert('Please enter text content.');
                    return;
                }
            } else if (contentType === 'quiz') {
                const questions = $('.quiz-question');
                let validQuiz = true;
                
                questions.each(function() {
                    const question = $(this).find('input[name="quiz_question[]"]').val();
                    const correctAnswer = $(this).find('select[name="quiz_correct_answer[]"]').val();
                    
                    if (!question.trim() || !correctAnswer) {
                        validQuiz = false;
                        return false;
                    }
                });
                
                if (!validQuiz) {
                    alert('Please complete all quiz questions with correct answers.');
                    return;
                }
            } else if (contentType === 'assignment') {
                const description = $('textarea[name="assignment_description"]').val();
                if (!description.trim()) {
                    alert('Please enter assignment description.');
                    return;
                }
            }
            
            // Show loading state
            $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
            
            // Submit form
            const formData = new FormData(this);
            
            $.ajax({
                url: '../api/save_lesson.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Lesson saved successfully!');
                        window.location.href = 'course_builder.php?id=<?php echo $courseId; ?>';
                    } else {
                        alert('Error saving lesson: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error saving lesson. Please try again.');
                },
                complete: function() {
                    $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Lesson');
                }
            });
        });

        // Initialize with video section visible
        $('#contentType').trigger('change');
    </script>
</body>
</html>
