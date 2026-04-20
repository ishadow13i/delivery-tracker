<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BatchResource\Pages;
use App\Models\Batch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BatchResource extends Resource
{
    protected static ?string $model = Batch::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Orders';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->maxLength(255),
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\DatePicker::make('date')
                ->required()
                ->default(now()),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $state ?: "Batch #{$record->id}"),
                Tables\Columns\TextColumn::make('company.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('creator.name')->label('Created By'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Orders'),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBatches::route('/'),
            'create' => Pages\CreateBatch::route('/create'),
            'edit' => Pages\EditBatch::route('/{record}/edit'),
        ];
    }
}
