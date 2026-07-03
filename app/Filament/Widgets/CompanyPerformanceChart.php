<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Company;
use Filament\Widgets\ChartWidget;

class CompanyPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'أداء شركات التوصيل';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $companies = Company::withCount([
            'orders as delivered_count' => fn ($q) => $q->where('status', OrderStatus::Delivered),
            'orders as rejected_count' => fn ($q) => $q->where('status', OrderStatus::Rejected)
                ->orWhere('status', OrderStatus::Returned)
                ->orWhere('status', OrderStatus::Missing),
        ])->get();

        return [
            'datasets' => [
                [
                    'label' => 'تم التوصيل',
                    'data' => $companies->pluck('delivered_count'),
                    'backgroundColor' => '#34D399',
                ],
                [
                    'label' => 'مرفوضة/مُرتجعة/مفقودة',
                    'data' => $companies->pluck('rejected_count'),
                    'backgroundColor' => '#EF4444',
                ],
            ],
            'labels' => $companies->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
