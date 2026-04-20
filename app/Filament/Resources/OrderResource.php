<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\StatusLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Orders';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracking_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('batch.name')
                    ->formatStateUsing(fn ($state, $record) => $state ?: ($record->batch_id ? "Batch #{$record->batch_id}" : '-'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\SelectColumn::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->sortable()
                    ->afterStateUpdated(function ($record, $state) {
                        $oldStatus = $record->getOriginal('status');
                        $timestamps = [];
                        if ($state === 'delivered') $timestamps['delivered_at'] = now();
                        if ($state === 'rejected') $timestamps['rejected_at'] = now();
                        if ($state === 'returned') $timestamps['returned_at'] = now();
                        if (!empty($timestamps)) {
                            $record->updateQuietly($timestamps);
                        }
                        StatusLog::create([
                            'order_id' => $record->id,
                            'changed_by' => auth()->id(),
                            'old_status' => $oldStatus,
                            'new_status' => $state,
                        ]);
                    }),
                Tables\Columns\TextColumn::make('dispatched_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rejected_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('returned_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name'),
                Tables\Filters\SelectFilter::make('batch')
                    ->relationship('batch', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?: "Batch #{$record->id}"),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
