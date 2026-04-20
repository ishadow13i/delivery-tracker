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
            Infolists\Components\Section::make('Order Details')->schema([
                Infolists\Components\TextEntry::make('tracking_number')->copyable(),
                Infolists\Components\TextEntry::make('company.name'),
                Infolists\Components\TextEntry::make('batch.name'),
                Infolists\Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color()),
                Infolists\Components\TextEntry::make('notes'),
            ])->columns(2),

            Infolists\Components\Section::make('Timeline')->schema([
                Infolists\Components\TextEntry::make('created_at')->dateTime(),
                Infolists\Components\TextEntry::make('dispatched_at')->dateTime(),
                Infolists\Components\TextEntry::make('delivered_at')->dateTime(),
                Infolists\Components\TextEntry::make('rejected_at')->dateTime(),
                Infolists\Components\TextEntry::make('returned_at')->dateTime(),
            ])->columns(3),

            Infolists\Components\Section::make('Scan History')->schema([
                Infolists\Components\RepeatableEntry::make('scanLogs')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')->badge(),
                        Infolists\Components\TextEntry::make('barcode_raw')->label('Barcode'),
                        Infolists\Components\TextEntry::make('user.name')->label('Scanned By'),
                        Infolists\Components\TextEntry::make('scanned_at')->dateTime(),
                    ])->columns(4),
            ]),

            Infolists\Components\Section::make('Status History')->schema([
                Infolists\Components\RepeatableEntry::make('statusLogs')
                    ->schema([
                        Infolists\Components\TextEntry::make('old_status')->badge(),
                        Infolists\Components\TextEntry::make('new_status')->badge(),
                        Infolists\Components\TextEntry::make('changedBy.name')->label('Changed By'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime()->label('Date'),
                        Infolists\Components\TextEntry::make('notes'),
                    ])->columns(5),
            ]),
        ]);
    }
}
