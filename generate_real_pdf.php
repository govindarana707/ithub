<?php
require_once 'config/config.php';
require_once 'simple_pdf_generator.php';

// Get certificate ID from URL
$certificateId = $_GET['id'] ?? '';
if (empty($certificateId)) {
    die('Certificate ID required');
}

// Get certificate details from database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$stmt = $conn->prepare("
    SELECT c.*, u.full_name, u.email, co.title as course_title
    FROM certificates c
    JOIN users u ON c.student_id = u.id
    JOIN courses_new co ON c.course_id = co.id
    WHERE c.certificate_id = ? AND c.status = 'issued'
");
$stmt->bind_param("s", $certificateId);
$stmt->execute();
$certificate = $stmt->get_result()->fetch_assoc();

if (!$certificate) {
    die('Certificate not found');
}

// Check if user has permission
if (!isset($_SESSION['user_id']) || 
    ($_SESSION['user_id'] != $certificate['student_id'] && $_SESSION['user_role'] !== 'admin')) {
    die('Access denied');
}

// Generate PDF using SimplePDF
$pdf = new SimplePDF('L', 'mm', 'A4');

// Set up the certificate
$pdf->SetXY(0, 30);
$pdf->SetFont('Arial', 'B', 24);
$pdf->Cell(297, 10, 'Certificate of Completion', 0, 1, 'C');

$pdf->SetFont('Arial', 'I', 16);
$pdf->Cell(297, 8, 'This is to certify that', 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 28);
$pdf->Cell(297, 12, $certificate['full_name'], 0, 1, 'C');

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 20);
$pdf->Cell(297, 10, $certificate['course_title'], 0, 1, 'C');

$pdf->Ln(15);
$pdf->SetFont('Arial', '', 14);
$pdf->MultiCell(257, 8, 'has successfully completed the course requirements and demonstrated proficiency in all subject areas.', 0, 'C');

$pdf->Ln(25);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(297, 8, 'Date: ' . date('F j, Y', strtotime($certificate['issued_date'])), 0, 1, 'C');

$pdf->SetXY(250, 180);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(40, 6, 'ID: ' . $certificate['certificate_id'], 0, 0, 'R');

// Output PDF
$filename = 'Certificate_' . $certificateId . '.pdf';
$pdf->Output($filename, 'D');

$conn->close();
?>
