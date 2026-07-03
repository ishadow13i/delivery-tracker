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
            Stat::make('إجمالي الطلبات', Order::count())
                ->description('كل الطلبات')
                ->icon('heroicon-o-clipboard-document-list'),

            Stat::make('المرسلة اليوم', Order::whereDate('dispatched_at', $today)->count())
                ->description('التي تم إرسالها اليوم')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning'),

            Stat::make('تم التوصيل', Order::where('status', OrderStatus::Delivered)->count())
                ->description('تم توصيلها بنجاح')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('مرفوضة ولم تُرجع', Order::where('status', OrderStatus::Rejected)->whereNull('returned_at')->count())
                ->description('بحاجة إلى انتباه')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
