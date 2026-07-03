<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfExtractor
{
    public function extractTrackingNumbers(string $pdfPath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $trackingNumbers = [];

        // Pattern 1: "رقم الطلب : XXXXXXXXXXXX" (10-15 digits)
        if (preg_match_all('/رقم الطلب\s*:?\s*(\d{10,15})/u', $text, $matches)) {
            $trackingNumbers = array_merge($trackingNumbers, $matches[1]);
        }

        // Deduplicate (numbers appear twice per page - once as text, once under QR)
        $trackingNumbers = array_values(array_unique($trackingNumbers));

        return $trackingNumbers;
    }
}
