<?php

namespace App\Services;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

class PdfExtractor
{
    public function extractTrackingNumbers(string $pdfPath): array
    {
        $config = new Config();
        $config->setRetainImageContent(false);

        $parser = new Parser([], $config);
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();

        // Find all sequences of exactly 12 digits (Datum PDF tracking format)
        preg_match_all('/(?<!\d)(\d{12})(?!\d)/', $text, $matches);

        $numbers = $matches[1] ?? [];

        // Exclude Iraqi phone numbers (start with 07)
        $numbers = array_filter($numbers, fn ($n) => !str_starts_with($n, '07'));

        // Deduplicate - tracking numbers appear twice per page
        return array_values(array_unique($numbers));
    }
}
