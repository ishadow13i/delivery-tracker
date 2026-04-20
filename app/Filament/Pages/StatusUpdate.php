<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\StatusLog;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class StatusUpdate extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'Status Update';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.status-update';

    public ?int $selectedCompanyId = null;
    public string $filterStatus = '';
    public string $search = '';

    public function getCompaniesProperty(): \Illuminate\Support\Collection
    {
        return Company::where('is_active', true)->get();
    }

    public function getOrdersProperty(): \Illuminate\Support\Collection
    {
        if (!$this->selectedCompanyId) {
            return collect();
        }

        return Order::where('company_id', $this->selectedCompanyId)
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when(!$this->filterStatus, fn ($q) => $q->whereIn('status', [
                OrderStatus::Dispatched->value,
                OrderStatus::Delayed->value,
            ]))
            ->when($this->search, fn ($q) => $q->where('tracking_number', 'like', "%{$this->search}%"))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    }

    public function updateStatus(int $orderId, string $newStatus): void
    {
        $order = Order::findOrFail($orderId);
        $oldStatus = $order->status;
        $newStatusEnum = OrderStatus::from($newStatus);

        if ($oldStatus === $newStatusEnum) {
            return;
        }

        $timestamps = [];
        if ($newStatusEnum === OrderStatus::Delivered) {
            $timestamps['delivered_at'] = now();
        } elseif ($newStatusEnum === OrderStatus::Rejected) {
            $timestamps['rejected_at'] = now();
        } elseif ($newStatusEnum === OrderStatus::Returned) {
            $timestamps['returned_at'] = now();
        }

        $order->update(['status' => $newStatus, ...$timestamps]);

        StatusLog::create([
            'order_id' => $order->id,
            'changed_by' => auth()->id(),
            'old_status' => $oldStatus->value,
            'new_status' => $newStatus,
        ]);

        Notification::make()
            ->title("{$order->tracking_number}: {$oldStatus->label()} → {$newStatusEnum->label()}")
            ->success()
            ->send();
    }

    public function bulkUpdateStatus(string $newStatus): void
    {
        $orders = $this->orders;
        $count = 0;

        foreach ($orders as $order) {
            if ($order->status->value === $newStatus) {
                continue;
            }
            $this->updateStatus($order->id, $newStatus);
            $count++;
        }

        Notification::make()
            ->title("Updated {$count} orders to " . OrderStatus::from($newStatus)->label())
            ->success()
            ->send();
    }
}
