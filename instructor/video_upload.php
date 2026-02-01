<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Upload - Course Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
        }
        
        .upload-method-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .upload-method-card:hover {
            border-color: #007bff;
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,123,255,0.2);
        }
        
        .upload-method-card.active {
            border-color: #007bff;
            background: linear-gradient(135deg, #f8f9ff, #e6f3ff);
        }
        
        .upload-method-card i {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .file-drop-zone {
            border: 3px dashed #ddd;
            border-radius: 15px;
            padding: 60px 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-drop-zone:hover,
        .file-drop-zone.dragover {
            border-color: #007bff;
            background: #e6f3ff;
        }
        
        .file-drop-zone i {
            font-size: 4rem;
            color: #007bff;
            margin-bottom: 20px;
        }
        
        .progress-container {
            display: none;
            margin-top: 20px;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #007bff, #0056b3);
            transition: width 0.3s ease;
        }
        
        .video-preview {
            display: none;
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .video-list-item {
            border: 1px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .video-list-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .video-thumbnail {
            width: 120px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .upload-method-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff, #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .url-input-group {
            display: none;
            margin-top: 20px;
        }
        
        .file-info {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-ready {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .status-processing {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }
        
        .status-error {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="upload-container">
                    <h1 class="mb-4">
                        <i class="fas fa-video me-3"></i>Video Upload Center
                    </h1>
                    <p class="lead mb-0">Upload course videos, embed YouTube/Vimeo links, and manage your video content</p>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Upload Method Selection -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Choose Upload Method
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="upload-method-card" data-method="direct">
                                    <div class="upload-method-icon mx-auto">
                                        <i class="fas fa-upload"></i>
                                    </div>
                                    <h5>Direct Upload</h5>
                                    <p class="text-muted small">Upload video files directly to server (MP4, AVI, MOV, WMV)</p>
                                    <div class="text-muted small">Max size: 500MB</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="upload-method-card" data-method="youtube">
                                    <div class="upload-method-icon mx-auto">
                                        <i class="fab fa-youtube"></i>
                                    </div>
                                    <h5>YouTube</h5>
                                    <p class="text-muted small">Embed YouTube videos by URL</p>
                                    <div class="text-muted small">No file size limit</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="upload-method-card" data-method="vimeo">
                                    <div class="upload-method-icon mx-auto">
                                        <i class="fab fa-vimeo"></i>
                                    </div>
                                    <h5>Vimeo</h5>
                                    <p class="text-muted small">Embed Vimeo videos by URL</p>
                                    <div class="text-muted small">Professional quality</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Form -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form id="videoUploadForm">
                            <input type="hidden" name="upload_method" id="uploadMethod" value="direct">
                            
                            <!-- Course Selection -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-graduation-cap me-2"></i>Course
                                    </label>
                                    <select class="form-select" name="course_id" required>
                                        <option value="">Select Course</option>
                                        <!-- Courses will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-book-open me-2"></i>Lesson (Optional)
                                    </label>
                                    <select class="form-select" name="lesson_id">
                                        <option value="">Select Lesson</option>
                                        <!-- Lessons will be loaded based on course -->
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Video Details -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-heading me-2"></i>Video Title
                                    </label>
                                    <input type="text" class="form-control" name="video_title" required 
                                           placeholder="Enter video title">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-align-left me-2"></i>Description
                                    </label>
                                    <textarea class="form-control" name="video_description" rows="1"
                                              placeholder="Brief description of the video content"></textarea>
                                </div>
                            </div>
                            
                            <!-- Direct Upload Area -->
                            <div id="directUploadArea">
                                <div class="file-drop-zone" id="fileDropZone">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <h4>Drop video file here or click to browse</h4>
                                    <p class="text-muted">Supported formats: MP4, AVI, MOV, WMV, WebM, FLV</p>
                                    <p class="text-muted">Maximum file size: 500MB</p>
                                    <input type="file" id="videoFile" name="video_file" accept="video/*" style="display: none;">
                                </div>
                                
                                <div class="file-info" id="fileInfo" style="display: none;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file-video me-3 text-primary"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold" id="fileName"></div>
                                            <div class="text-muted small" id="fileSize"></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- YouTube URL Input -->
                            <div class="url-input-group" id="youtubeUrlGroup">
                                <label class="form-label fw-bold">
                                    <i class="fab fa-youtube me-2"></i>YouTube Video URL
                                </label>
                                <input type="url" class="form-control" name="video_url" 
                                       placeholder="https://www.youtube.com/watch?v=...">
                                <div class="form-text">Enter the full YouTube video URL</div>
                            </div>
                            
                            <!-- Vimeo URL Input -->
                            <div class="url-input-group" id="vimeoUrlGroup">
                                <label class="form-label fw-bold">
                                    <i class="fab fa-vimeo me-2"></i>Vimeo Video URL
                                </label>
                                <input type="url" class="form-control" name="video_url" 
                                       placeholder="https://vimeo.com/...">
                                <div class="form-text">Enter the full Vimeo video URL</div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="progress-container" id="progressContainer">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold">Uploading...</span>
                                    <span id="progressPercent">0%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5" id="uploadBtn">
                                    <i class="fas fa-upload me-2"></i>Upload Video
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Recent Videos -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-history me-2"></i>Recent Uploads
                        </h3>
                        <div id="recentVideos">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-video fa-3x mb-3 opacity-50"></i>
                                <p>No videos uploaded yet</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Tips -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-lightbulb me-2"></i>Upload Tips
                        </h5>
                        <ul class="small">
                            <li>Use MP4 format for best compatibility</li>
                            <li>Keep videos under 500MB for direct upload</li>
                            <li>Use YouTube/Vimeo for large files</li>
                            <li>Add descriptive titles and descriptions</li>
                            <li>Organize videos by course and lesson</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let selectedFile = null;
            
            // Upload method selection
            $('.upload-method-card').on('click', function() {
                $('.upload-method-card').removeClass('active');
                $(this).addClass('active');
                
                const method = $(this).data('method');
                $('#uploadMethod').val(method);
                
                // Hide all upload areas
                $('#directUploadArea, .url-input-group').hide();
                
                // Show relevant upload area
                if (method === 'direct') {
                    $('#directUploadArea').show();
                } else if (method === 'youtube') {
                    $('#youtubeUrlGroup').show();
                } else if (method === 'vimeo') {
                    $('#vimeoUrlGroup').show();
                }
            });
            
            // Set default method
            $('.upload-method-card[data-method="direct"]').click();
            
            // File drop zone
            const fileDropZone = $('#fileDropZone');
            const videoFileInput = $('#videoFile');
            
            fileDropZone.on('click', function() {
                videoFileInput.click();
            });
            
            fileDropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            });
            
            fileDropZone.on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });
            
            fileDropZone.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelect(files[0]);
                }
            });
            
            videoFileInput.on('change', function(e) {
                if (e.target.files.length > 0) {
                    handleFileSelect(e.target.files[0]);
                }
            });
            
            function handleFileSelect(file) {
                // Validate file type
                const allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm', 'video/flv'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid video format. Please select MP4, AVI, MOV, WMV, WebM, or FLV.');
                    return;
                }
                
                // Validate file size (500MB)
                const maxSize = 500 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('File size exceeds 500MB limit. Please use YouTube/Vimeo for larger files.');
                    return;
                }
                
                selectedFile = file;
                displayFileInfo(file);
            }
            
            function displayFileInfo(file) {
                $('#fileName').text(file.name);
                $('#fileSize').text(formatFileSize(file.size));
                $('#fileInfo').show();
                fileDropZone.hide();
            }
            
            $('#removeFile').on('click', function() {
                selectedFile = null;
                videoFileInput.val('');
                $('#fileInfo').hide();
                fileDropZone.show();
            });
            
            // Form submission
            $('#videoUploadForm').on('submit', function(e) {
                e.preventDefault();
                
                const uploadMethod = $('#uploadMethod').val();
                
                // Validate based on upload method
                if (uploadMethod === 'direct' && !selectedFile) {
                    alert('Please select a video file to upload.');
                    return;
                }
                
                if ((uploadMethod === 'youtube' || uploadMethod === 'vimeo') && !$('input[name="video_url"]').val()) {
                    alert('Please enter the video URL.');
                    return;
                }
                
                // Create FormData
                const formData = new FormData(this);
                
                if (uploadMethod === 'direct' && selectedFile) {
                    formData.append('video_file', selectedFile);
                }
                
                // Show progress
                $('#progressContainer').show();
                $('#uploadBtn').prop('disabled', true);
                
                // Submit via AJAX
                $.ajax({
                    url: '../api/video_upload.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(e) {
                            if (e.lengthComputable) {
                                const percentComplete = (e.loaded / e.total) * 100;
                                $('#progressBar').css('width', percentComplete + '%');
                                $('#progressPercent').text(Math.round(percentComplete) + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Video uploaded successfully!');
                            resetForm();
                            loadRecentVideos();
                        } else {
                            alert('Upload failed: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Upload failed. Please try again.');
                    },
                    complete: function() {
                        $('#progressContainer').hide();
                        $('#uploadBtn').prop('disabled', false);
                        $('#progressBar').css('width', '0%');
                        $('#progressPercent').text('0%');
                    }
                });
            });
            
            function resetForm() {
                $('#videoUploadForm')[0].reset();
                selectedFile = null;
                $('#fileInfo').hide();
                fileDropZone.show();
                $('.upload-method-card[data-method="direct"]').click();
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            // Load courses and recent videos
            loadCourses();
            loadRecentVideos();
        });
        
        function loadCourses() {
            // Load real courses from API
            $.ajax({
                url: '../api/instructor_courses.php',
                type: 'GET',
                success: function(response) {
                    const courseSelect = $('select[name="course_id"]');
                    courseSelect.empty();
                    courseSelect.append('<option value="">Select Course</option>');
                    
                    if (response.success && response.courses) {
                        response.courses.forEach(course => {
                            courseSelect.append(`<option value="${course.id}">${course.title}</option>`);
                        });
                    } else {
                        // Fallback to sample courses if API fails
                        const courses = [
                            {id: 1, title: 'Web Development Fundamentals'},
                            {id: 2, title: 'Advanced PHP Programming'},
                            {id: 3, title: 'Database Design'}
                        ];
                        
                        courses.forEach(course => {
                            courseSelect.append(`<option value="${course.id}">${course.title}</option>`);
                        });
                    }
                },
                error: function() {
                    // Fallback to sample courses on error
                    const courseSelect = $('select[name="course_id"]');
                    courseSelect.empty();
                    courseSelect.append('<option value="">Select Course</option>');
                    
                    const courses = [
                        {id: 1, title: 'Web Development Fundamentals'},
                        {id: 2, title: 'Advanced PHP Programming'},
                        {id: 3, title: 'Database Design'}
                    ];
                    
                    courses.forEach(course => {
                        courseSelect.append(`<option value="${course.id}">${course.title}</option>`);
                    });
                }
            });
        }
        
        function loadRecentVideos() {
            $.ajax({
                url: '../api/video_upload.php',
                type: 'GET',
                success: function(response) {
                    if (response.success && response.videos.length > 0) {
                        displayRecentVideos(response.videos);
                    }
                }
            });
        }
        
        function displayRecentVideos(videos) {
            const container = $('#recentVideos');
            container.empty();
            
            videos.slice(0, 5).forEach(video => {
                const videoItem = `
                    <div class="video-list-item">
                        <div class="d-flex align-items-center">
                            <div class="video-thumbnail me-3">
                                <i class="fas fa-play"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${video.title}</h6>
                                <div class="text-muted small">
                                    <i class="fas fa-graduation-cap me-1"></i>${video.course_title}
                                    ${video.duration ? ' • ' + video.duration : ''}
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-${video.status}">${video.status}</span>
                            </div>
                        </div>
                    </div>
                `;
                container.append(videoItem);
            });
        }
    </script>
</body>
</html>
