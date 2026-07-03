<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">شركة التوصيل</label>
                    <select wire:model.live="selectedCompanyId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">جميع الشركات</option>
                        @foreach($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">من</label>
                    <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">إلى</label>
                    <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">بحث</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="رقم التتبع أو نص الملاحظة..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">إجمالي الملاحظات</p>
                <p class="text-3xl font-bold text-primary-600">{{ $this->notes->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">طلبات مختلفة</p>
                <p class="text-3xl font-bold text-blue-600">{{ $this->notes->pluck('order_id')->unique()->count() }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">ملاحظات اليوم</p>
                <p class="text-3xl font-bold text-green-600">{{ $this->notes->filter(fn($n) => $n->created_at->isToday())->count() }}</p>
            </div>
        </div>

        {{-- Notes table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">التاريخ</th>
                        <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">رقم التتبع</th>
                        <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">شركة التوصيل</th>
                        <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">الملاحظة</th>
                        <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">أُضيفت بواسطة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($this->notes as $note)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                                {{ $note->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-4 py-3 font-mono font-semibold text-gray-900 dark:text-white">
                                @if($note->order)
                                    <a href="{{ route('filament.admin.resources.orders.view', ['record' => $note->order->id]) }}"
                                       class="hover:text-primary-600 hover:underline">
                                        {{ $note->order->tracking_number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $note->order?->company?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                                {{ $note->notes }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $note->changedBy?->name ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                لا توجد ملاحظات تخطي لهذه التصفية.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
