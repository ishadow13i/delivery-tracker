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
    protected static ?string $navigationGroup = 'الطلبات';
    protected static ?string $navigationLabel = 'الدُفعات';
    protected static ?string $modelLabel = 'دُفعة';
    protected static ?string $pluralModelLabel = 'الدُفعات';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('اسم الدُفعة')
                ->maxLength(255),
            Forms\Components\Select::make('company_id')
                ->label('شركة التوصيل')
                ->relationship('company', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\DatePicker::make('date')
                ->label('التاريخ')
                ->required()
                ->default(now()),
            Forms\Components\Textarea::make('notes')
                ->label('ملاحظات')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الدُفعة')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $state ?: "دُفعة #{$record->id}"),
                Tables\Columns\TextColumn::make('company.name')->label('شركة التوصيل')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('creator.name')->label('أنشأها'),
                Tables\Columns\TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('عدد الطلبات'),
                Tables\Columns\TextColumn::make('date')->label('التاريخ')->date()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->label('شركة التوصيل')
                    ->relationship('company', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
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
