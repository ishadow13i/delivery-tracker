<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'الإعدادات';
    protected static ?string $navigationLabel = 'شركات التوصيل';
    protected static ?string $modelLabel = 'شركة توصيل';
    protected static ?string $pluralModelLabel = 'شركات التوصيل';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('اسم الشركة')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->label('رقم الهاتف')
                ->tel()
                ->maxLength(255),
            Forms\Components\TextInput::make('contact_name')
                ->label('اسم جهة الاتصال')
                ->maxLength(255),
            Forms\Components\Toggle::make('is_active')
                ->label('نشط')
                ->default(true),
            Forms\Components\Textarea::make('notes')
                ->label('ملاحظات')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('اسم الشركة')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('رقم الهاتف'),
                Tables\Columns\TextColumn::make('contact_name')->label('جهة الاتصال'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('عدد الطلبات'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('نشط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
