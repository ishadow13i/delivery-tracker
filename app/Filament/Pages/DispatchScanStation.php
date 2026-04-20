<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Enums\ScanType;
use App\Models\Batch;
use App\Models\Order;
use App\Models\ScanLog;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class DispatchScanStation extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin', 'scanner']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Scanning';
    protected static ?string $navigationLabel = 'Dispatch Scan';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.dispatch-scan-station';

    public ?int $selectedBatchId = null;
    public string $barcodeInput = '';
    public array $scanResults = [];
    public int $scannedCount = 0;

    public function getBatchesProperty(): \Illuminate\Support\Collection
    {
        return Batch::with('company')
            ->withCount('orders')
            ->latest()
            ->limit(50)
            ->get();
    }

    public function selectBatch(int $batchId): void
    {
        $this->selectedBatchId = $batchId;
        $this->scanResults = [];
        $this->scannedCount = Batch::find($batchId)?->orders()->count() ?? 0;
    }

    public function scan(): void
    {
        $barcode = trim($this->barcodeInput);
        $this->barcodeInput = '';

        if (empty($barcode)) {
            return;
        }

        // Check if already dispatched in this batch
        $existingOrder = Order::where('tracking_number', $barcode)->first();

        if ($existingOrder) {
            if ($existingOrder->batch_id === $this->selectedBatchId && $existingOrder->status !== OrderStatus::Assigned && $existingOrder->status !== OrderStatus::Created) {
                array_unshift($this->scanResults, [
                    'barcode' => $barcode,
                    'success' => false,
                    'message' => "Already dispatched in this batch",
                    'time' => now()->format('H:i:s'),
                ]);
                Notification::make()->title('Already dispatched')->warning()->send();
                $this->dispatch('scan-error');
                return;
            }

            if ($existingOrder->batch_id !== $this->selectedBatchId && $existingOrder->batch_id !== null) {
                array_unshift($this->scanResults, [
                    'barcode' => $barcode,
                    'success' => false,
                    'message' => "Already exists in another batch!",
                    'time' => now()->format('H:i:s'),
                ]);
                Notification::make()->title('Exists in another batch')->danger()->send();
                $this->dispatch('scan-error');
                return;
            }
        }

        $batch = Batch::find($this->selectedBatchId);

        if (!$existingOrder) {
            // Auto-create the order
            $existingOrder = Order::create([
                'tracking_number' => $barcode,
                'company_id' => $batch->company_id,
                'batch_id' => $batch->id,
                'status' => OrderStatus::Dispatched,
                'dispatched_at' => now(),
            ]);
        } else {
            // Update existing order
            $existingOrder->update([
                'company_id' => $batch->company_id,
                'batch_id' => $batch->id,
                'status' => OrderStatus::Dispatched,
                'dispatched_at' => now(),
            ]);
        }

        ScanLog::create([
            'order_id' => $existingOrder->id,
            'user_id' => auth()->id(),
            'type' => ScanType::Dispatch,
            'barcode_raw' => $barcode,
            'scanned_at' => now(),
        ]);

        $this->scannedCount++;

        array_unshift($this->scanResults, [
            'barcode' => $barcode,
            'success' => true,
            'message' => 'Dispatched',
            'time' => now()->format('H:i:s'),
        ]);

        Notification::make()->title("Dispatched: {$barcode}")->success()->send();
        $this->dispatch('scan-success');
    }
}
