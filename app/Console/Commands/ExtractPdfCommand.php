<?php

namespace App\Console\Commands;

use App\Services\PdfExtractor;
use Illuminate\Console\Command;

class ExtractPdfCommand extends Command
{
    protected $signature = 'pdf:extract {jobId}';
    protected $description = 'Extract tracking numbers from a PDF in the background';

    public function handle(): int
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $jobId = $this->argument('jobId');
        $pdfPath = storage_path('app/pdf-imports/' . $jobId . '.pdf');
        $resultPath = storage_path('app/pdf-imports/' . $jobId . '.json');

        if (!file_exists($pdfPath)) {
            file_put_contents($resultPath, json_encode([
                'status' => 'error',
                'error' => 'PDF file not found',
            ]));
            return self::FAILURE;
        }

        try {
            $extractor = new PdfExtractor();
            $numbers = $extractor->extractTrackingNumbers($pdfPath);

            file_put_contents($resultPath, json_encode([
                'status' => 'done',
                'numbers' => array_values($numbers),
                'count' => count($numbers),
            ]));
        } catch (\Throwable $e) {
            file_put_contents($resultPath, json_encode([
                'status' => 'error',
                'error' => $e->getMessage(),
            ]));
            return self::FAILURE;
        } finally {
            @unlink($pdfPath);
        }

        return self::SUCCESS;
    }
}
