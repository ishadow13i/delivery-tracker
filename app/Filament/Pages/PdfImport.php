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
        $this->reset(['pdfFile', 'batchName', 'existingBatchId', 'useExistingBatch', 'extractedNumbers', 'newNumbers', 'updatableNumbers', 'duplicateNumbers', 'showPreview']);
    }

    public function backToCompanySelection(): void
    {
        $this->reset(['selectedCompanyId', 'pdfFile', 'batchName', 'existingBatchId', 'useExistingBatch', 'extractedNumbers', 'newNumbers', 'updatableNumbers', 'duplicateNumbers', 'showPreview']);
    }

    public function extractFromPdf(): void
    {
        if (!$this->pdfFile) {
            Notification::make()->title('يرجى رفع ملف PDF')->danger()->send();
            return;
        }

        try {
            $path = $this->pdfFile->getRealPath();
            $extractor = new PdfExtractor();
            $numbers = $extractor->extractTrackingNumbers($path);

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
                ->title("تم العثور على " . count($numbers) . " رقم تتبع")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('فشل قراءة ملف PDF')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function confirmImport(): void
    {
        if (empty($this->extractedNumbers) || !$this->selectedCompanyId) {
            return;
        }

        // If nothing to create or update, abort without creating an empty batch
        if (empty($this->newNumbers) && empty($this->updatableNumbers)) {
            Notification::make()
                ->title('لا يوجد ما نستورده — كل الأرقام موجودة بالفعل')
                ->warning()
                ->send();
            $this->cancelPreview();
            return;
        }

        // Create or find batch
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
        $updated = 0;

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

        foreach ($this->updatableNumbers as $item) {
            $order = Order::where('tracking_number', $item['tracking'])->first();
            if ($order) {
                $order->update([
                    'company_id' => $this->selectedCompanyId,
                    'batch_id' => $batch->id,
                    'status' => OrderStatus::Dispatched,
                    'dispatched_at' => now(),
                ]);
                $updated++;
            }
        }

        $skipped = count($this->duplicateNumbers);

        $msg = "دُفعة #{$batch->id}: تم إنشاء {$created}";
        if ($updated > 0) $msg .= "، تم تحديث {$updated}";
        if ($skipped > 0) $msg .= "، تم تجاهل {$skipped}";

        Notification::make()
            ->title($msg)
            ->success()
            ->persistent()
            ->send();

        // Reset for next import
        $this->reset(['pdfFile', 'batchName', 'existingBatchId', 'useExistingBatch', 'extractedNumbers', 'newNumbers', 'updatableNumbers', 'duplicateNumbers', 'showPreview']);
    }

    public function cancelPreview(): void
    {
        $this->reset(['extractedNumbers', 'newNumbers', 'updatableNumbers', 'duplicateNumbers', 'showPreview']);
    }
}
