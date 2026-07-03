<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Models\Batch;
use App\Models\Company;
use App\Models\Order;
use App\Services\PdfExtractor;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
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
    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'PDF Import';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.pdf-import';

    public ?int $selectedCompanyId = null;
    public $pdfFile = null;
    public string $batchName = '';
    public ?int $existingBatchId = null;
    public bool $useExistingBatch = false;
    public array $extractedNumbers = [];
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
        $this->reset(['pdfFile', 'batchName', 'existingBatchId', 'useExistingBatch', 'extractedNumbers', 'showPreview']);
    }

    public function backToCompanySelection(): void
    {
        $this->reset(['selectedCompanyId', 'pdfFile', 'batchName', 'existingBatchId', 'useExistingBatch', 'extractedNumbers', 'showPreview']);
    }

    public function extractFromPdf(): void
    {
        if (!$this->pdfFile) {
            Notification::make()->title('Please upload a PDF file')->danger()->send();
            return;
        }

        try {
            $path = $this->pdfFile->getRealPath();
            $extractor = new PdfExtractor();
            $numbers = $extractor->extractTrackingNumbers($path);

            if (empty($numbers)) {
                Notification::make()
                    ->title('No tracking numbers found in the PDF')
                    ->body('Make sure the PDF is text-based (not scanned image). Try opening it and selecting text with your mouse.')
                    ->danger()
                    ->persistent()
                    ->send();
                return;
            }

            $this->extractedNumbers = $numbers;
            $this->showPreview = true;

            Notification::make()
                ->title("Found " . count($numbers) . " tracking numbers")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to parse PDF')
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
        $skipped = 0;
        $updated = 0;

        foreach ($this->extractedNumbers as $tracking) {
            $existing = Order::where('tracking_number', $tracking)->first();

            if (!$existing) {
                Order::create([
                    'tracking_number' => $tracking,
                    'company_id' => $this->selectedCompanyId,
                    'batch_id' => $batch->id,
                    'status' => OrderStatus::Assigned,
                ]);
                $created++;
            } elseif ($existing->status === OrderStatus::Created || $existing->status === OrderStatus::Assigned) {
                $existing->update([
                    'company_id' => $this->selectedCompanyId,
                    'batch_id' => $batch->id,
                    'status' => OrderStatus::Assigned,
                ]);
                $updated++;
            } else {
                $skipped++;
            }
        }

        $msg = "Batch #{$batch->id}: {$created} created";
        if ($updated > 0) $msg .= ", {$updated} updated";
        if ($skipped > 0) $msg .= ", {$skipped} skipped (already dispatched/delivered)";

        Notification::make()
            ->title($msg)
            ->success()
            ->persistent()
            ->send();

        // Reset for next import
        $this->reset(['pdfFile', 'batchName', 'existingBatchId', 'useExistingBatch', 'extractedNumbers', 'showPreview']);
    }

    public function cancelPreview(): void
    {
        $this->reset(['extractedNumbers', 'showPreview']);
    }
}
