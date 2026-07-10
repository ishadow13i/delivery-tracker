<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Order;
use App\Services\PdfExtractor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class PdfImport extends Page implements HasForms
{
    use InteractsWithForms, WithFileUploads;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?string $navigationGroup = 'الطلبات';
    protected static ?string $navigationLabel = 'استيراد PDF';
    protected static ?string $title = 'استيراد PDF';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.pdf-import';

    public ?int $selectedCompanyId = null;
    public $pdfFile = null;
    public string $batchName = '';
    public ?int $existingBatchId = null;
    public bool $useExistingBatch = false;
    public array $extractedNumbers = [];
    public array $newNumbers = [];
    public array $updatableNumbers = [];
    public array $duplicateNumbers = [];
    public bool $showPreview = false;

    public ?string $jobId = null;
    public bool $processing = false;
    public string $processingMessage = '';

    public function getCompaniesProperty(): \Illuminate\Support\Collection
    {
        return Company::where('is_active', true)->get();
    }

    public function getExistingBatchesProperty(): \Illuminate\Support\Collection
    {
        if (!$this->selectedCompanyId) {
            return collect();
        }

        return Batch::where('company_id', $this->selectedCompanyId)
            ->whereDate('date', '>=', now()->subDays(7))
            ->withCount('orders')
            ->latest()
            ->get();
    }

    public function selectCompany(int $companyId): void
    {
        $this->selectedCompanyId = $companyId;
        $this->resetImport();
    }

    public function backToCompanySelection(): void
    {
        $this->selectedCompanyId = null;
        $this->resetImport();
    }

    protected function resetImport(): void
    {
        $this->reset([
            'pdfFile', 'batchName', 'existingBatchId', 'useExistingBatch',
            'extractedNumbers', 'newNumbers', 'updatableNumbers', 'duplicateNumbers',
            'showPreview', 'jobId', 'processing', 'processingMessage',
        ]);
    }

    public function extractFromPdf(): void
    {
        if (!$this->pdfFile) {
            Notification::make()->title('يرجى رفع ملف PDF')->danger()->send();
            return;
        }

        // Save the uploaded PDF to a persistent location
        $jobId = (string) Str::uuid();
        $pdfDir = storage_path('app/pdf-imports');
        if (!is_dir($pdfDir)) {
            @mkdir($pdfDir, 0755, true);
        }
        $pdfPath = $pdfDir . '/' . $jobId . '.pdf';
        copy($this->pdfFile->getRealPath(), $pdfPath);

        // Kick off background extraction (does not block the request)
        $this->jobId = $jobId;
        $this->processing = true;
        $this->processingMessage = 'جاري استخراج الأرقام...';

        $php = PHP_BINARY ?: '/usr/local/bin/php';
        $artisan = base_path('artisan');
        $cmd = sprintf(
            '%s %s pdf:extract %s > /dev/null 2>&1 &',
            escapeshellcmd($php),
            escapeshellarg($artisan),
            escapeshellarg($jobId)
        );

        if (function_exists('exec')) {
            exec($cmd);
        } else {
            // Fallback: process synchronously if exec is unavailable
            \Artisan::call('pdf:extract', ['jobId' => $jobId]);
        }
    }

    public function pollExtraction(): void
    {
        if (!$this->jobId || !$this->processing) {
            return;
        }

        $resultPath = storage_path('app/pdf-imports/' . $this->jobId . '.json');

        if (!file_exists($resultPath)) {
            return;
        }

        $result = json_decode(file_get_contents($resultPath), true);

        if (!$result) {
            return;
        }

        $this->processing = false;

        if (($result['status'] ?? '') === 'error') {
            Notification::make()
                ->title('فشل قراءة ملف PDF')
                ->body($result['error'] ?? 'حدث خطأ غير متوقع')
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        $numbers = $result['numbers'] ?? [];

        if (empty($numbers)) {
            Notification::make()
                ->title('لم يتم العثور على أرقام تتبع في الملف')
                ->body('تأكد من أن الملف نصي (وليس صورة ممسوحة). جرّب فتحه وتحديد النص بالفأرة.')
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        // Categorize each number
        $existingOrders = Order::whereIn('tracking_number', $numbers)->get()->keyBy('tracking_number');

        $new = [];
        $duplicate = [];

        foreach ($numbers as $tn) {
            $existing = $existingOrders->get($tn);

            if (!$existing) {
                $new[] = $tn;
            } else {
                $duplicate[] = ['tracking' => $tn, 'currentStatus' => $existing->status->label()];
            }
        }

        $this->extractedNumbers = $numbers;
        $this->newNumbers = $new;
        $this->updatableNumbers = [];
        $this->duplicateNumbers = $duplicate;
        $this->showPreview = true;

        Notification::make()
            ->title('تم العثور على ' . count($numbers) . ' رقم تتبع')
            ->success()
            ->send();
    }

    public function confirmImport(): void
    {
        if (empty($this->extractedNumbers) || !$this->selectedCompanyId) {
            return;
        }

        if (empty($this->newNumbers) && empty($this->updatableNumbers)) {
            Notification::make()
                ->title('لا يوجد ما نستورده — كل الأرقام موجودة بالفعل')
                ->warning()
                ->send();
            $this->cancelPreview();
            return;
        }

        if ($this->useExistingBatch && $this->existingBatchId) {
            $batch = Batch::find($this->existingBatchId);
        } else {
            $batch = Batch::create([
                'company_id' => $this->selectedCompanyId,
                'created_by' => auth()->id(),
                'name' => $this->batchName ?: 'PDF Import ' . now()->format('Y-m-d H:i'),
                'date' => now(),
            ]);
        }

        $created = 0;

        foreach ($this->newNumbers as $tracking) {
            Order::create([
                'tracking_number' => $tracking,
                'company_id' => $this->selectedCompanyId,
                'batch_id' => $batch->id,
                'status' => OrderStatus::Dispatched,
                'dispatched_at' => now(),
            ]);
            $created++;
        }

        $skipped = count($this->duplicateNumbers);

        $msg = "دُفعة #{$batch->id}: تم إنشاء {$created}";
        if ($skipped > 0) $msg .= "، تم تجاهل {$skipped}";

        Notification::make()
            ->title($msg)
            ->success()
            ->persistent()
            ->send();

        $this->resetImport();
    }

    public function cancelPreview(): void
    {
        $this->reset(['extractedNumbers', 'newNumbers', 'updatableNumbers', 'duplicateNumbers', 'showPreview', 'jobId', 'processing', 'processingMessage']);
    }
}
