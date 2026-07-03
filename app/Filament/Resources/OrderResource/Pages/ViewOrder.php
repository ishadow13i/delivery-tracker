<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('تفاصيل الطلب')->schema([
                Infolists\Components\TextEntry::make('tracking_number')->label('رقم التتبع')->copyable(),
                Infolists\Components\TextEntry::make('company.name')->label('شركة التوصيل'),
                Infolists\Components\TextEntry::make('batch.name')->label('الدُفعة'),
                Infolists\Components\TextEntry::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->color()),
                Infolists\Components\TextEntry::make('notes')->label('الملاحظات'),
            ])->columns(2),

            Infolists\Components\Section::make('الجدول الزمني')->schema([
                Infolists\Components\TextEntry::make('created_at')->label('تاريخ الإنشاء')->dateTime(),
                Infolists\Components\TextEntry::make('dispatched_at')->label('تاريخ الإرسال')->dateTime(),
                Infolists\Components\TextEntry::make('delivered_at')->label('تاريخ التوصيل')->dateTime(),
                Infolists\Components\TextEntry::make('rejected_at')->label('تاريخ الرفض')->dateTime(),
                Infolists\Components\TextEntry::make('returned_at')->label('تاريخ الإرجاع')->dateTime(),
            ])->columns(3),

            Infolists\Components\Section::make('سجل المسح الضوئي')->schema([
                Infolists\Components\RepeatableEntry::make('scanLogs')
                    ->label('')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')->label('النوع')->badge(),
                        Infolists\Components\TextEntry::make('barcode_raw')->label('الباركود'),
                        Infolists\Components\TextEntry::make('user.name')->label('بواسطة'),
                        Infolists\Components\TextEntry::make('scanned_at')->label('تاريخ المسح')->dateTime(),
                    ])->columns(4),
            ]),

            Infolists\Components\Section::make('سجل تغيير الحالة')->schema([
                Infolists\Components\RepeatableEntry::make('statusLogs')
                    ->label('')
                    ->schema([
                        Infolists\Components\TextEntry::make('old_status')
                            ->label('الحالة السابقة')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof OrderStatus ? $state->label() : (string) $state),
                        Infolists\Components\TextEntry::make('new_status')
                            ->label('الحالة الجديدة')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof OrderStatus ? $state->label() : (string) $state),
                        Infolists\Components\TextEntry::make('changedBy.name')->label('بواسطة'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime()->label('التاريخ'),
                        Infolists\Components\TextEntry::make('notes')->label('ملاحظات'),
                    ])->columns(5),
            ]),
        ]);
    }
}
