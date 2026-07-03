<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Enums\ScanType;
use App\Models\Order;
use App\Models\ScanLog;
use App\Models\StatusLog;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ReturnScanStation extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin', 'scanner']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationGroup = 'المسح الضوئي';
    protected static ?string $navigationLabel = 'مسح الإرجاع';
    protected static ?string $title = 'مسح الإرجاع';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.return-scan-station';

    public string $barcodeInput = '';
    public string $noteTrackingNumber = '';
    public string $noteText = '';
    public array $scanResults = [];
    public array $skippedNotes = [];
    public int $returnedToday = 0;

    public function mount(): void
    {
        $this->returnedToday = Order::whereDate('returned_at', today())->count();
    }

    protected function findOrderByBarcode(string $barcode): ?Order
    {
        // First try direct tracking number match
        $order = Order::where('tracking_number', $barcode)->first();
        if ($order) {
            return $order;
        }

        // Then try finding via scan_logs barcode_raw (in case barcode differs)
        $scanLog = ScanLog::where('barcode_raw', $barcode)->first();
        if ($scanLog) {
            return $scanLog->order;
        }

        return null;
    }

    public function scan(): void
    {
        $barcode = trim($this->barcodeInput);
        $this->barcodeInput = '';

        if (empty($barcode)) {
            return;
        }

        $order = $this->findOrderByBarcode($barcode);

        if (!$order) {
            array_unshift($this->scanResults, [
                'barcode' => $barcode,
                'success' => false,
                'message' => 'الطلب غير موجود في النظام!',
                'time' => now()->format('H:i:s'),
                'company' => null,
            ]);

            Notification::make()->title("غير موجود: {$barcode}")->danger()->send();
            $this->dispatch('scan-error');
            return;
        }

        if ($order->status === OrderStatus::Returned) {
            array_unshift($this->scanResults, [
                'barcode' => $barcode,
                'success' => false,
                'message' => 'تم وضع علامة مُرتجع بالفعل!',
                'time' => now()->format('H:i:s'),
                'company' => $order->company?->name,
            ]);

            Notification::make()->title('تم إرجاعه بالفعل')->warning()->send();
            $this->dispatch('scan-error');
            return;
        }

        $oldStatus = $order->status;

        $order->update([
            'status' => OrderStatus::Returned,
            'returned_at' => now(),
        ]);

        ScanLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'type' => ScanType::Return,
            'barcode_raw' => $barcode,
            'scanned_at' => now(),
        ]);

        StatusLog::create([
            'order_id' => $order->id,
            'changed_by' => auth()->id(),
            'old_status' => $oldStatus->value,
            'new_status' => OrderStatus::Returned->value,
            'notes' => 'Scanned at return station',
        ]);

        $this->returnedToday++;

        array_unshift($this->scanResults, [
            'barcode' => $barcode,
            'success' => true,
            'message' => "تم الإرجاع — شركة: {$order->company?->name} — #{$order->tracking_number}",
            'time' => now()->format('H:i:s'),
            'company' => $order->company?->name,
        ]);

        Notification::make()->title("تم الإرجاع: {$order->tracking_number}")->success()->send();
        $this->dispatch('scan-success');
    }

    public function saveNote(): void
    {
        $tracking = trim($this->noteTrackingNumber);
        $note = trim($this->noteText);

        if (empty($tracking) || empty($note)) {
            Notification::make()->title('يرجى إدخال رقم التتبع والملاحظة')->danger()->send();
            return;
        }

        $order = $this->findOrderByBarcode($tracking);

        if ($order) {
            $oldNotes = $order->notes;
            $timestamp = now()->format('Y-m-d H:i');
            $newNote = $oldNotes
                ? $oldNotes . "\n[{$timestamp}] {$note}"
                : "[{$timestamp}] {$note}";
            $order->update(['notes' => $newNote]);

            StatusLog::create([
                'order_id' => $order->id,
                'changed_by' => auth()->id(),
                'old_status' => $order->status->value,
                'new_status' => $order->status->value,
                'notes' => $note,
            ]);
        }

        array_unshift($this->skippedNotes, [
            'tracking' => $tracking,
            'note' => $note,
            'time' => now()->format('H:i:s'),
            'found' => (bool) $order,
        ]);

        $this->noteTrackingNumber = '';
        $this->noteText = '';

        Notification::make()->title("تم حفظ الملاحظة للطلب {$tracking}")->success()->send();
    }
}
