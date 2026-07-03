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

        // Find all sequences of exactly 12 digits (Datum PDF tracking format)
        // Word boundaries prevent matching 12 digits inside longer number sequences
        preg_match_all('/(?<!\d)(\d{12})(?!\d)/', $text, $matches);

        $numbers = $matches[1] ?? [];

        // Exclude anything that looks like an Iraqi phone number (starts with 07)
        // Phones are 11 digits so shouldn't match, but safety filter regardless
        $numbers = array_filter($numbers, fn ($n) => !str_starts_with($n, '07'));

        // Deduplicate - tracking numbers appear twice per page (as text + under QR)
        return array_values(array_unique($numbers));
    }
}
