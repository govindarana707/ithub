<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

$instructorId = $_SESSION['user_id'];
$csrfToken = generateCSRFToken();

error_log("DEBUG: Loading instructor profile for user_id: " . $instructorId);

$conn = connectDB();

if (!$conn) {
    error_log("DEBUG: Database connection failed in instructor profile");
}

// Load current user data
$stmt = $conn->prepare("SELECT id, username, email, full_name, profile_image, bio, phone FROM users WHERE id = ?");
$stmt->bind_param('i', $instructorId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

error_log("DEBUG: User query result: " . ($user ? "found" : "not found") . " for user_id: " . $instructorId);

if (!$user) {
    $conn->close();
    $_SESSION['error_message'] = 'User not found.';
    header('Location: dashboard.php');
    exit;
}

// Load instructor_meta
$meta = [];
$stmt = $conn->prepare("SELECT meta_key, meta_value FROM instructor_meta WHERE instructor_id = ?");
$stmt->bind_param('i', $instructorId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($rows as $r) {
    $k = $r['meta_key'];
    $v = $r['meta_value'];
    if ($k === 'specialties' || $k === 'qualifications') {
        $meta[$k] = json_decode($v, true) ?: [];
    } elseif ($k === 'social_links') {
        $meta[$k] = json_decode($v, true) ?: [];
    } else {
        $meta[$k] = $v;
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($postedToken)) {
        $_SESSION['error_message'] = 'Invalid request token. Please refresh and try again.';
        header('Location: profile.php');
        exit;
    }

    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');

    if ($fullName === '' || strlen($fullName) < 2) {
        $_SESSION['error_message'] = 'Full name must be at least 2 characters.';
        header('Location: profile.php');
        exit;
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Valid email is required.';
        header('Location: profile.php');
        exit;
    }

    // Avatar upload
    $avatarPath = $user['profile_image'] ?? null;
    if (isset($_FILES['avatar']) && is_array($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = uploadFile($_FILES['avatar'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'avatars');
        if (!($upload['success'] ?? false)) {
            $_SESSION['error_message'] = 'Avatar upload failed: ' . ($upload['message'] ?? 'Unknown error');
            header('Location: profile.php');
            exit;
        }
        $avatarPath = $upload['filename'];
    }

    // Update users table
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, bio = ?, profile_image = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('sssssi', $fullName, $email, $phone, $bio, $avatarPath, $instructorId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        $_SESSION['error_message'] = 'Failed to update profile.';
        header('Location: profile.php');
        exit;
    }

    // Update instructor_meta
    $specialties = array_filter(array_map('trim', (array)($_POST['specialties'] ?? [])), function ($v) { return $v !== ''; });
    $qualifications = array_filter(array_map('trim', (array)($_POST['qualifications'] ?? [])), function ($v) { return $v !== ''; });
    $socialLinks = [
        'linkedin' => sanitize($_POST['social_linkedin'] ?? ''),
        'twitter' => sanitize($_POST['social_twitter'] ?? ''),
        'github' => sanitize($_POST['social_github'] ?? ''),
        'website' => sanitize($_POST['social_website'] ?? ''),
    ];

    $stmt = $conn->prepare("INSERT INTO instructor_meta (instructor_id, meta_key, meta_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = NOW()");
    $stmt->bind_param('iss', $instructorId, $key, $json);

    $key = 'specialties';
    $json = json_encode($specialties);
    $stmt->execute();

    $key = 'qualifications';
    $json = json_encode($qualifications);
    $stmt->execute();

    $key = 'social_links';
    $json = json_encode($socialLinks);
    $stmt->execute();

    $stmt->close();
    $conn->close();

    $_SESSION['success_message'] = 'Profile updated successfully!';
    logActivity($instructorId, 'profile_updated', 'Updated instructor profile');
    header('Location: profile.php');
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-soft {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        }
        .tag-input {
            display: inline-block;
            margin: 0.25rem;
            padding: 0.25rem 0.5rem;
            background: #e9ecef;
            border-radius: 0.25rem;
            font-size: 0.9rem;
        }
        .tag-input button {
            background: none;
            border: none;
            margin-left: 0.5rem;
            cursor: pointer;
            color: #6c757d;
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
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                <a class="nav-link" href="courses.php"><i class="fas fa-chalkboard-teacher me-1"></i> My Courses</a>
                <a class="nav-link" href="students.php"><i class="fas fa-users me-1"></i> Students</a>
                <a class="nav-link" href="quizzes.php"><i class="fas fa-question-circle me-1"></i> Quizzes</a>
                <a class="nav-link" href="analytics.php"><i class="fas fa-chart-line me-1"></i> Analytics</a>
                <a class="nav-link" href="earnings.php"><i class="fas fa-rupee-sign me-1"></i> Earnings</a>
                <a class="nav-link active" href="profile.php"><i class="fas fa-user me-1"></i> Profile</a>
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
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
                        <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                    </a>
                    <a href="create-course.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i> Create Course
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-question-circle me-2"></i> Quizzes
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-rupee-sign me-2"></i> Earnings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="mb-1">Profile</h1>
                        <div class="text-muted">Manage your instructor profile and public information</div>
                    </div>
                </div>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="row g-3">
                        <div class="col-md-4 text-center">
                            <div class="card-soft">
                                <h5 class="mb-3">Profile Picture</h5>
                                <img src="<?php
                                    $img = $user['profile_image'] ?? '';
                                    echo $img ? htmlspecialchars(resolveUploadUrl($img)) : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?? 'User') . '&size=120&background=667eea&color=fff';
                                ?>" class="avatar-preview mb-3" alt="Avatar">
                                <div class="mb-3">
                                    <label class="form-label">Change Avatar</label>
                                    <input type="file" name="avatar" class="form-control" accept="image/*">
                                    <div class="form-text">JPG, PNG, GIF, WEBP</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="card-soft">
                                <h5 class="mb-3">Basic Information</h5>

                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" disabled>
                                    <div class="form-text">Username cannot be changed</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bio</label>
                                    <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    <div class="form-text">Brief description about yourself (visible to students)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-soft mt-3">
                        <h5 class="mb-3">Professional Details</h5>

                        <div class="mb-3">
                            <label class="form-label">Specialties</label>
                            <div id="specialtiesContainer">
                                <?php foreach (($meta['specialties'] ?? []) as $s): ?>
                                    <span class="tag-input">
                                        <?php echo htmlspecialchars($s); ?>
                                        <button type="button" onclick="this.parentElement.remove()">×</button>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <div class="input-group mt-2">
                                <input type="text" class="form-control" id="specialtyInput" placeholder="Add a specialty">
                                <button class="btn btn-outline-secondary" type="button" onclick="addSpecialty()">Add</button>
                            </div>
                            <div class="form-text">e.g., Web Development, JavaScript, React</div>
                            <input type="hidden" name="specialties[]" id="specialtiesHidden" value="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Qualifications</label>
                            <div id="qualificationsContainer">
                                <?php foreach (($meta['qualifications'] ?? []) as $q): ?>
                                    <span class="tag-input">
                                        <?php echo htmlspecialchars($q); ?>
                                        <button type="button" onclick="this.parentElement.remove()">×</button>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <div class="input-group mt-2">
                                <input type="text" class="form-control" id="qualificationInput" placeholder="Add a qualification">
                                <button class="btn btn-outline-secondary" type="button" onclick="addQualification()">Add</button>
                            </div>
                            <div class="form-text">e.g., BSc Computer Science, AWS Certified</div>
                            <input type="hidden" name="qualifications[]" id="qualificationsHidden" value="">
                        </div>
                    </div>

                    <div class="card-soft mt-3">
                        <h5 class="mb-3">Social Links</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">LinkedIn</label>
                                <input type="url" name="social_linkedin" class="form-control" value="<?php echo htmlspecialchars(($meta['social_links']['linkedin'] ?? '')); ?>" placeholder="https://linkedin.com/in/...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Twitter</label>
                                <input type="url" name="social_twitter" class="form-control" value="<?php echo htmlspecialchars(($meta['social_links']['twitter'] ?? '')); ?>" placeholder="https://twitter.com/...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">GitHub</label>
                                <input type="url" name="social_github" class="form-control" value="<?php echo htmlspecialchars(($meta['social_links']['github'] ?? '')); ?>" placeholder="https://github.com/...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="url" name="social_website" class="form-control" value="<?php echo htmlspecialchars(($meta['social_links']['website'] ?? '')); ?>" placeholder="https://...">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addSpecialty() {
            const input = document.getElementById('specialtyInput');
            const val = input.value.trim();
            if (!val) return;
            const container = document.getElementById('specialtiesContainer');
            const span = document.createElement('span');
            span.className = 'tag-input';
            span.innerHTML = val + '<button type="button" onclick="this.parentElement.remove()">×</button>';
            container.appendChild(span);
            input.value = '';
            updateSpecialtiesHidden();
        }
        function addQualification() {
            const input = document.getElementById('qualificationInput');
            const val = input.value.trim();
            if (!val) return;
            const container = document.getElementById('qualificationsContainer');
            const span = document.createElement('span');
            span.className = 'tag-input';
            span.innerHTML = val + '<button type="button" onclick="this.parentElement.remove()">×</button>';
            container.appendChild(span);
            input.value = '';
            updateQualificationsHidden();
        }
        function updateSpecialtiesHidden() {
            const container = document.getElementById('specialtiesContainer');
            const tags = Array.from(container.querySelectorAll('.tag-input')).map(el => el.textContent.replace('×', '').trim());
            document.getElementById('specialtiesHidden').value = JSON.stringify(tags);
        }
        function updateQualificationsHidden() {
            const container = document.getElementById('qualificationsContainer');
            const tags = Array.from(container.querySelectorAll('.tag-input')).map(el => el.textContent.replace('×', '').trim());
            document.getElementById('qualificationsHidden').value = JSON.stringify(tags);
        }
        document.getElementById('specialtyInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); addSpecialty(); }
        });
        document.getElementById('qualificationInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); addQualification(); }
        });
        // Initialize hidden fields on load
        updateSpecialtiesHidden();
        updateQualificationsHidden();
    </script>
</body>
</html>
