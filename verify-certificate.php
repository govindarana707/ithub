<?php
require_once 'config/config.php';

$certificateCode = sanitize($_GET['code'] ?? '');

if (empty($certificateCode)) {
    header('HTTP/1.0 400 Bad Request');
    echo '<h1>Invalid Certificate Code</h1>';
    echo '<p>Please provide a valid certificate code.</p>';
    exit;
}

// Get certificate details
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as student_name, u.email as student_email,
           co.title as course_title, co.description as course_description,
           ins.full_name as instructor_name
    FROM certificates c
    JOIN users u ON c.student_id = u.id
    JOIN courses co ON c.course_id = co.id
    JOIN users ins ON co.instructor_id = ins.id
    WHERE c.certificate_code = ?
");
$stmt->bind_param("s", $certificateCode);
$stmt->execute();
$certificate = $stmt->get_result()->fetch_assoc();

$conn->close();

if (!$certificate) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>Certificate Not Found</h1>';
    echo '<p>The certificate code you provided is not valid or the certificate does not exist.</p>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .verification-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 2rem auto;
        }
        .verification-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .certificate-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="verification-card">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="verification-success">
                        <i class="fas fa-check fa-2x"></i>
                    </div>
                    <h2>Certificate Verified</h2>
                    <p class="text-muted">This certificate is authentic and valid</p>
                </div>

                <div class="certificate-details">
                    <h4 class="mb-4">Certificate Information</h4>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Certificate Code:</strong>
                        </div>
                        <div class="col-md-8">
                            <code><?php echo htmlspecialchars($certificate['certificate_code']); ?></code>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Student Name:</strong>
                        </div>
                        <div class="col-md-8">
                            <?php echo htmlspecialchars($certificate['student_name']); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Email:</strong>
                        </div>
                        <div class="col-md-8">
                            <?php echo htmlspecialchars($certificate['student_email']); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Course Title:</strong>
                        </div>
                        <div class="col-md-8">
                            <?php echo htmlspecialchars($certificate['course_title']); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Instructor:</strong>
                        </div>
                        <div class="col-md-8">
                            <?php echo htmlspecialchars($certificate['instructor_name']); ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Date Issued:</strong>
                        </div>
                        <div class="col-md-8">
                            <?php echo date('F j, Y', strtotime($certificate['issued_at'])); ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <strong>Verification Date:</strong>
                        </div>
                        <div class="col-md-8">
                            <?php echo date('F j, Y g:i A'); ?>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>About IT HUB Certificates:</strong><br>
                    IT HUB certificates are awarded to students who successfully complete our courses. 
                    Each certificate has a unique verification code that can be used to confirm its authenticity.
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i>Visit IT HUB
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
