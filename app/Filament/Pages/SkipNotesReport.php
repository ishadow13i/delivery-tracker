<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\StatusLog;
use Filament\Pages\Page;

class SkipNotesReport extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'التقارير';
    protected static ?string $navigationLabel = 'ملاحظات التخطي';
    protected static ?string $title = 'ملاحظات التخطي';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.skip-notes-report';

    public ?int $selectedCompanyId = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public string $search = '';

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getCompaniesProperty(): \Illuminate\Support\Collection
    {
        return Company::all();
    }

    public function getNotesProperty(): \Illuminate\Support\Collection
    {
        return StatusLog::query()
            ->with(['order.company', 'changedBy'])
            ->whereNotNull('notes')
            ->whereRaw('old_status = new_status')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->selectedCompanyId, fn ($q) => $q->whereHas('order', fn ($sub) => $sub->where('company_id', $this->selectedCompanyId)))
            ->when($this->search, fn ($q) => $q->where(function ($sub) {
                $sub->whereHas('order', fn ($o) => $o->where('tracking_number', 'like', "%{$this->search}%"))
                    ->orWhere('notes', 'like', "%{$this->search}%");
            }))
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();
    }
}
