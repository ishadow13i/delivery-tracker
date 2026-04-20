<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use Filament\Pages\Page;

class ReconciliationReport extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Reconciliation';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.reconciliation-report';

    public ?int $selectedCompanyId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getCompaniesProperty(): \Illuminate\Support\Collection
    {
        return Company::all();
    }

    public function getReportDataProperty(): array
    {
        $query = Order::query()
            ->when($this->selectedCompanyId, fn ($q) => $q->where('company_id', $this->selectedCompanyId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo));

        $totalOrders = (clone $query)->count();
        $dispatched = (clone $query)->where('status', '!=', OrderStatus::Created->value)
            ->where('status', '!=', OrderStatus::Assigned->value)->count();
        $delivered = (clone $query)->where('status', OrderStatus::Delivered->value)->count();
        $rejected = (clone $query)->where('status', OrderStatus::Rejected->value)->count();
        $returned = (clone $query)->where('status', OrderStatus::Returned->value)->count();
        $delayed = (clone $query)->where('status', OrderStatus::Delayed->value)->count();
        $missing = (clone $query)->where('status', OrderStatus::Missing->value)->count();

        $rejectedNotReturned = (clone $query)
            ->where('status', OrderStatus::Rejected->value)
            ->whereNull('returned_at')
            ->count();

        return [
            'total_orders' => $totalOrders,
            'dispatched' => $dispatched,
            'delivered' => $delivered,
            'rejected' => $rejected,
            'returned' => $returned,
            'delayed' => $delayed,
            'missing' => $missing,
            'rejected_not_returned' => $rejectedNotReturned,
            'delivery_rate' => $dispatched > 0 ? round(($delivered / $dispatched) * 100, 1) : 0,
            'rejection_rate' => $dispatched > 0 ? round(($rejected / $dispatched) * 100, 1) : 0,
        ];
    }

    public function getMissingOrdersProperty(): \Illuminate\Support\Collection
    {
        return Order::with('company')
            ->where('status', OrderStatus::Rejected->value)
            ->whereNull('returned_at')
            ->when($this->selectedCompanyId, fn ($q) => $q->where('company_id', $this->selectedCompanyId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->orderBy('rejected_at')
            ->get();
    }

    public function getCompanyBreakdownProperty(): \Illuminate\Support\Collection
    {
        return Company::withCount([
            'orders' => fn ($q) => $q
                ->when($this->dateFrom, fn ($q2) => $q2->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q2) => $q2->whereDate('created_at', '<=', $this->dateTo)),
            'orders as delivered_count' => fn ($q) => $q->where('status', OrderStatus::Delivered->value)
                ->when($this->dateFrom, fn ($q2) => $q2->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q2) => $q2->whereDate('created_at', '<=', $this->dateTo)),
            'orders as rejected_count' => fn ($q) => $q->where('status', OrderStatus::Rejected->value)
                ->when($this->dateFrom, fn ($q2) => $q2->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q2) => $q2->whereDate('created_at', '<=', $this->dateTo)),
            'orders as returned_count' => fn ($q) => $q->where('status', OrderStatus::Returned->value)
                ->when($this->dateFrom, fn ($q2) => $q2->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q2) => $q2->whereDate('created_at', '<=', $this->dateTo)),
            'orders as missing_count' => fn ($q) => $q
                ->where('status', OrderStatus::Rejected->value)
                ->whereNull('returned_at')
                ->when($this->dateFrom, fn ($q2) => $q2->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn ($q2) => $q2->whereDate('created_at', '<=', $this->dateTo)),
        ])->get();
    }

    public function markAsMissing(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => OrderStatus::Missing]);
    }
}
