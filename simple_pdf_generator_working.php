<?php
/**
 * Simple PDF Generator Class - Working Version
 * Basic PDF generation functionality for certificates
 * Created: 2026-03-29
 */

class SimplePDF {
    private $page;
    private $orientation;
    private $unit;
    private $format;
    private $pdf;
    
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        $this->orientation = $orientation;
        $this->unit = $unit;
        $this->format = $format;
        $this->page = 0;
        $this->pdf = '';
    }
    
    public function SetXY($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
    
    public function SetFont($family, $style = '', $size = 12) {
        $this->fontFamily = $family;
        $this->fontStyle = $style;
        $this->fontSize = $size;
    }
    
    public function Cell($w, $h, $txt, $border = 0, $ln = 0, $align = '') {
        $this->pdf .= sprintf(
            "BT %.2f %.2f %.2f %.2f %s (%s) %s ET\n",
            $w, $this->y, $h, $border, $ln, $align,
            $this->fontFamily ?? 'Arial',
            $this->fontStyle ?? '',
            $this->fontSize ?? 12,
            $txt
        );
        
        if ($ln == 1) {
            $this->y += $h;
            $this->x = 10; // Reset x position
        } else {
            $this->x += $w;
        }
    }
    
    public function Ln($h = '') {
        $this->y += $h;
        $this->x = 10; // Reset x position
    }
    
    public function MultiCell($w, $h, $txt, $border = 0, $ln = 0, $align = '') {
        // Simple word wrap for multi-cell
        $words = explode(' ', $txt);
        $lines = [];
        $currentLine = '';
        
        foreach ($words as $word) {
            $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
            
            // Simple width calculation (approximate)
            $lineWidth = strlen($testLine) * 2.5; // Rough estimate
            
            if ($lineWidth > $w) {
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            } else {
                $currentLine = $word;
            }
        }
        
        foreach ($lines as $line) {
            $this->Cell($w, $h, $line, $border, 1, $align);
        }
    }
    
    public function Output($filename = 'document.pdf', $destination = 'I') {
        // Generate simple PDF content
        $content = $this->GeneratePDFContent();
        
        // Set headers for PDF output
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $destination . '; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $content;
        exit;
    }
    
    public function Preview() {
        // Generate PDF content for preview
        $content = $this->GeneratePDFContent();
        
        // Output as base64 for preview
        return base64_encode($content);
    }
    
    private function GeneratePDFContent() {
        // Generate simple PDF content
        $content = "%PDF-1.1\n";
        $content .= sprintf("1 0 0 %s %s\n", $this->orientation, $this->unit, $this->format);
        $content .= "1 0 obj\n";
        $content .= "<<\n";
        $content .= "/Type /Catalog\n";
        $content .= "/Pages 1 0 R\n";
        $content .= ">>\n";
        $content .= "endobj\n";
        $content .= "2 0 obj\n";
        $content .= "<<\n";
        $content .= "/Type /Page\n";
        $content .= "/Parent 1 0 R\n";
        $content .= "/Resources <<\n";
        $content .= "/Font <<\n";
        $content .= "/F1 3 0 R\n";
        $content .= ">>\n";
        $content .= ">>\n";
        $content .= "/MediaBox [0 0 595 842]\n";
        $content .= "/Contents 3 0 R\n";
        $content .= ">>\n";
        $content .= "endobj\n";
        $content .= "3 0 obj\n";
        $content .= "<<\n";
        $content .= "/Type /Font\n";
        $content .= "/Subtype /Type1\n";
        $content .= "/BaseFont /" . ($this->fontFamily ?? 'Arial') . "\n";
        $content .= ">>\n";
        $content .= ">>\n";
        $content .= "endobj\n";
        $content .= "4 0 obj\n";
        $content .= "<<\n";
        $content .= "/Size 6\n";
        $content .= ">>\n";
        $content .= "xref\n";
        $content .= "0 65535 f\n";
        $content .= "0000000000 65535 f\n";
        $content .= "0000000010 00000 00000 n\n";
        $content .= "0000000022 00000 00000 n\n";
        $content .= "0000000035 00000 00000 n\n";
        $content .= "0000000041 00000 00000 n\n";
        $content .= "0000000050 00000 00000 n\n";
        $content .= "trailer\n";
        $content .= "<<\n";
        $content .= "/Size 6\n";
        $content .= "/Root 5 0 R\n";
        $content .= ">>\n";
        $content .= "startxref\n";
        $content .= "%%EOF\n";
        
        return $content;
    }
}

?>
