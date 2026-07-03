<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            </div>
        </div>

        {{-- Summary stats --}}
        @php $data = $this->reportData; @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">إجمالي الطلبات</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $data['total_orders'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">تم التوصيل</p>
                <p class="text-2xl font-bold text-green-600">{{ $data['delivered'] }}</p>
                <p class="text-xs text-gray-400">نسبة التوصيل {{ $data['delivery_rate'] }}%</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">مرفوضة</p>
                <p class="text-2xl font-bold text-red-600">{{ $data['rejected'] }}</p>
                <p class="text-xs text-gray-400">نسبة الرفض {{ $data['rejection_rate'] }}%</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">مُرتجعة</p>
                <p class="text-2xl font-bold text-blue-600">{{ $data['returned'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-300 dark:border-red-700 p-4 {{ $data['rejected_not_returned'] > 0 ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                <p class="text-sm text-gray-500">مرفوضة ولم تُرجع</p>
                <p class="text-2xl font-bold text-red-600">{{ $data['rejected_not_returned'] }}</p>
                @if($data['rejected_not_returned'] > 0)
                    <p class="text-xs text-red-500 font-semibold">تتطلب انتباه</p>
                @endif
            </div>
        </div>

        {{-- Company Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">تفصيل حسب شركة التوصيل</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">الشركة</th>
                        <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">الإجمالي</th>
                        <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">تم التوصيل</th>
                        <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">مرفوضة</th>
                        <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">مُرتجعة</th>
                        <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">مفقودة</th>
                        <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">نسبة التوصيل</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($this->companyBreakdown as $company)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $company->name }}</td>
                            <td class="px-4 py-3 text-center">{{ $company->orders_count }}</td>
                            <td class="px-4 py-3 text-center text-green-600 font-medium">{{ $company->delivered_count }}</td>
                            <td class="px-4 py-3 text-center text-red-600 font-medium">{{ $company->rejected_count }}</td>
                            <td class="px-4 py-3 text-center text-blue-600 font-medium">{{ $company->returned_count }}</td>
                            <td class="px-4 py-3 text-center {{ $company->missing_count > 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                                {{ $company->missing_count }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($company->orders_count > 0)
                                    {{ round(($company->delivered_count / $company->orders_count) * 100, 1) }}%
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Missing orders detail --}}
        @if($this->missingOrders->isNotEmpty())
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl shadow-sm border border-red-300 dark:border-red-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-red-200 dark:border-red-700">
                    <h3 class="font-semibold text-red-800 dark:text-red-200">
                        مرفوضة ولم تُرجع فعلياً ({{ $this->missingOrders->count() }} طلب)
                    </h3>
                    <p class="text-sm text-red-600 dark:text-red-400">هذه الطلبات تم وضع علامة مرفوضة عليها من شركة التوصيل لكنها لم تُمسح فعلياً عند الإرجاع.</p>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-red-100/50 dark:bg-red-900/30">
                        <tr>
                            <th class="px-4 py-3 text-left text-red-700 dark:text-red-300">رقم التتبع</th>
                            <th class="px-4 py-3 text-left text-red-700 dark:text-red-300">الشركة</th>
                            <th class="px-4 py-3 text-left text-red-700 dark:text-red-300">تاريخ الرفض</th>
                            <th class="px-4 py-3 text-left text-red-700 dark:text-red-300">أيام معلقة</th>
                            <th class="px-4 py-3 text-right text-red-700 dark:text-red-300">إجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-100 dark:divide-red-800">
                        @foreach($this->missingOrders as $order)
                            <tr class="hover:bg-red-100/50 dark:hover:bg-red-900/30">
                                <td class="px-4 py-3 font-mono font-semibold text-gray-900 dark:text-white">
                                    {{ $order->tracking_number }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $order->company?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                    {{ $order->rejected_at?->format('M d, Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($order->rejected_at)
                                        @php $days = $order->rejected_at->diffInDays(now()); @endphp
                                        <span class="{{ $days > 7 ? 'text-red-600 font-bold' : 'text-orange-600' }}">
                                            {{ $days }} يوم
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        wire:click="markAsMissing({{ $order->id }})"
                                        wire:confirm="وضع علامة مفقود على {{ $order->tracking_number }}؟ سيتم تصنيفه كمحتمل ضائع/مسروق."
                                        class="px-2 py-1 rounded bg-red-200 text-red-800 hover:bg-red-300 text-xs font-medium"
                                    >
                                        علامة مفقود
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
