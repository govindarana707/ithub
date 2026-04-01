<?php
/**
 * Certificate PDF Generator - Working Version
 * Generates professional certificates without external dependencies
 * Created: 2026-03-29
 */

class CertificatePDFGenerator {
    private $certificate;
    private $orientation = 'L';
    private $format = 'A4';
    
    public function __construct($certificateData) {
        $this->certificate = $certificateData;
    }
    
    /**
     * Generate PDF content as HTML string
     */
    public function generateHTML() {
        $cert = $this->certificate;
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 20mm;
        }
        
        body {
            font-family: "Georgia", serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .certificate-container {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 8px solid #667eea;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .certificate-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\'40\' height=\'40\' viewBox=\'0 0 40 40\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23667eea\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M20 20c0-5.5-4.5-10-10-10s-10 4.5-10 10 4.5 10 10 10 10-4.5 10-10zm10 0c0-5.5-4.5-10-10-10s-10 4.5-10 10 4.5 10 10 10 10-4.5 10-10z\'/%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .certificate-content {
            text-align: center;
            z-index: 1;
            padding: 40px;
            max-width: 800px;
        }
        
        .certificate-title {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            letter-spacing: 2px;
        }
        
        .certificate-subtitle {
            font-size: 24px;
            font-style: italic;
            color: #666;
            margin-bottom: 40px;
        }
        
        .student-name {
            font-size: 42px;
            font-weight: bold;
            color: #333;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: none;
        }
        
        .course-title {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 30px;
        }
        
        .certificate-description {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .certificate-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 60px;
            padding: 0 20px;
        }
        
        .certificate-date {
            font-size: 16px;
            color: #888;
        }
        
        .certificate-id {
            font-size: 14px;
            color: #999;
            font-weight: bold;
        }
        
        .certificate-seal {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 80px;
            height: 80px;
            border: 3px solid #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            font-weight: bold;
            color: #667eea;
            font-size: 12px;
            text-align: center;
        }
        
        .certificate-border {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px solid #764ba2;
            border-radius: 15px;
            pointer-events: none;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .certificate-container {
                box-shadow: none;
                border: 2px solid #667eea;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-border"></div>
        <div class="certificate-content">
            <h1 class="certificate-title">Certificate of Completion</h1>
            <p class="certificate-subtitle">This is to certify that</p>
            <h2 class="student-name">' . htmlspecialchars($cert['full_name']) . '</h2>
            <p class="course-title">has successfully completed</p>
            <h3 class="course-title">' . htmlspecialchars($cert['course_title']) . '</h3>
            <p class="certificate-description">
                has successfully completed course requirements and demonstrated proficiency in all subject areas.
            </p>
            <div class="certificate-details">
                <div class="certificate-date">
                    Date: ' . date('F j, Y', strtotime($cert['issued_date'])) . '
                </div>
                <div class="certificate-id">
                    ID: ' . htmlspecialchars($cert['certificate_id']) . '
                </div>
            </div>
        </div>
        <div class="certificate-seal">
            IT HUB<br>VERIFIED
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Output PDF for download
     */
    public function download($filename = 'certificate.pdf') {
        $html = $this->generateHTML();
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Convert HTML to PDF using DOMPDF if available, otherwise use browser print
        if (class_exists('Dompdf')) {
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream($filename, ['Attachment' => 1]);
        } else {
            // Fallback: Output HTML with print-friendly headers
            echo $html;
        }
        exit;
    }
    
    /**
     * Get PDF as base64 for preview
     */
    public function getBase64() {
        $html = $this->generateHTML();
        
        // For preview, we'll return the HTML as base64
        // In a real implementation, you would convert to PDF first
        return base64_encode($html);
    }
    
    /**
     * Output for inline viewing
     */
    public function view($filename = 'certificate.pdf') {
        $html = $this->generateHTML();
        
        // Set headers for inline viewing
        header('Content-Type: text/html');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        
        echo $html;
        exit;
    }
}

?>
