<?php

namespace App\Services;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;

class PdfExtractor
{
    public function extractTrackingNumbers(string $pdfPath): array
    {
        // Try Ghostscript first (much faster) — falls back to pure PHP parser if unavailable
        $text = $this->extractTextViaGhostscript($pdfPath) ?? $this->extractTextViaPdfParser($pdfPath);

        // Find all sequences of 12 or more digits (supports various delivery company formats)
        preg_match_all('/(?<!\d)(\d{12,})(?!\d)/', $text, $matches);

        $numbers = $matches[1] ?? [];

        // Exclude Iraqi phone numbers:
        //   - Local format: 11 digits starting with 07
        //   - International format: 13 digits starting with 964
        $numbers = array_filter($numbers, fn ($n) => !str_starts_with($n, '07') && !str_starts_with($n, '964'));

        // Deduplicate - tracking numbers appear twice per page
        return array_values(array_unique($numbers));
    }

    private function extractTextViaGhostscript(string $pdfPath): ?string
    {
        $gs = $this->findGhostscript();
        if (!$gs) {
            return null;
        }

        $outputPath = tempnam(sys_get_temp_dir(), 'pdftext_') . '.txt';

        $cmd = sprintf(
            '%s -q -dNOPAUSE -dBATCH -sDEVICE=txtwrite -sOutputFile=%s %s 2>&1',
            escapeshellcmd($gs),
            escapeshellarg($outputPath),
            escapeshellarg($pdfPath)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($outputPath)) {
            @unlink($outputPath);
            return null;
        }

        $text = file_get_contents($outputPath);
        @unlink($outputPath);

        return $text !== false ? $text : null;
    }

    private function extractTextViaPdfParser(string $pdfPath): string
    {
        $config = new Config();
        $config->setRetainImageContent(false);

        $parser = new Parser([], $config);
        $pdf = $parser->parseFile($pdfPath);

        return $pdf->getText();
    }

    private function findGhostscript(): ?string
    {
        if (!function_exists('exec')) {
            return null;
        }

        foreach (['/usr/bin/gs', '/usr/local/bin/gs'] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }
}
