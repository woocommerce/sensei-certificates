<?php

use PHPUnit\Framework\TestCase;

define('FPDF_FONT_WRITE_PATH', __DIR__ . '/../build/');

class PDFGeneratedTest extends TestCase
{
    public function testFileIsGenerated()
    {
        $pdfLibrary = new tFPDF\PDF();

        $pdfLibrary->AddPage();

        $pdfLibrary->AddFont('DejaVuSansCondensed', '', 'DejaVuSansCondensed.ttf', true);
        $pdfLibrary->SetFont('DejaVuSansCondensed', '', 14);

        $txt = file_get_contents(__DIR__ . '/test_data/HelloWorld.txt');
        $pdfLibrary->Write(8, $txt);

        $pdfLibrary->SetFont('Arial', '', 14);
        $pdfLibrary->Ln(10);
        $pdfLibrary->Write(5, "La taille de ce PDF n'est que de 12 ko.");

        $pdfLibrary->SetFont('Courier', '', 14);
        $pdfLibrary->Ln(10);
        $pdfLibrary->Write(5, "Hello Courier World");
        $pdfLibrary->SetFont('Courier', 'U', 14);
        $pdfLibrary->Ln(10);
        $pdfLibrary->Write(5, "Hello Underscored Courier World");

        $pdfLibrary->Ln(10);

        $file = $pdfLibrary->output();

        if (empty($file)) {
            static::fail("Empty PDF library output");
        }

        $file_name = __DIR__ . '/test_data/output.pdf';

        unlink($file_name);
        file_put_contents($file_name, $file);

        if (!file_exists($file_name)) {
            static::fail("PDF {$file_name} file does not exist");
        }
    }
}
