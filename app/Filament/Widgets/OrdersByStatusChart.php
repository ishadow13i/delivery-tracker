<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Orders by Status';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = collect(OrderStatus::cases())->map(function ($status) {
            return [
                'label' => $status->label(),
                'count' => Order::where('status', $status)->count(),
                'color' => match ($status) {
                    OrderStatus::Created => '#9CA3AF',
                    OrderStatus::Assigned => '#60A5FA',
                    OrderStatus::Dispatched => '#FBBF24',
                    OrderStatus::Delivered => '#34D399',
                    OrderStatus::Delayed => '#F59E0B',
                    OrderStatus::Rejected => '#EF4444',
                    OrderStatus::Returned => '#818CF8',
                    OrderStatus::Missing => '#DC2626',
                },
            ];
        })->filter(fn ($item) => $item['count'] > 0);

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('count')->values(),
                    'backgroundColor' => $data->pluck('color')->values(),
                ],
            ],
            'labels' => $data->pluck('label')->values(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
