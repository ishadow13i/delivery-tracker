<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->toDateString();

        return [
            Stat::make('Total Orders', Order::count())
                ->description('All time')
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make('Dispatched Today', Order::whereDate('dispatched_at', $today)->count())
                ->description('Scanned out today')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning'),

            Stat::make('Delivered', Order::where('status', OrderStatus::Delivered)->count())
                ->description('Successfully delivered')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Rejected (Not Returned)', Order::where('status', OrderStatus::Rejected)->whereNull('returned_at')->count())
                ->description('Needs attention')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
