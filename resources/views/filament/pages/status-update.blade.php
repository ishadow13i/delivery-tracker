<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company</label>
                    <select wire:model.live="selectedCompanyId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">Select Company...</option>
                        @foreach($this->companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Status</label>
                    <select wire:model.live="filterStatus" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">Dispatched & Delayed</option>
                        @foreach(\App\Enums\OrderStatus::cases() as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Tracking #</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
            </div>
        </div>

        @if($selectedCompanyId)
            {{-- Bulk actions --}}
            <div class="flex gap-2 flex-wrap items-center">
                <span class="text-sm text-gray-500 py-2">Bulk update all visible ({{ $this->orders->count() }}) to:</span>
                <button wire:click="bulkUpdateStatus('delivered')" wire:confirm="Mark all {{ $this->orders->count() }} visible orders as Delivered?" class="px-3 py-1 rounded-lg bg-green-100 text-green-800 hover:bg-green-200 text-sm font-medium">
                    All Delivered
                </button>
                <button wire:click="bulkUpdateStatus('rejected')" wire:confirm="Mark all {{ $this->orders->count() }} visible orders as Rejected?" class="px-3 py-1 rounded-lg bg-red-100 text-red-800 hover:bg-red-200 text-sm font-medium">
                    All Rejected
                </button>
                <button wire:click="bulkUpdateStatus('delayed')" wire:confirm="Mark all {{ $this->orders->count() }} visible orders as Delayed?" class="px-3 py-1 rounded-lg bg-yellow-100 text-yellow-800 hover:bg-yellow-200 text-sm font-medium">
                    All Delayed
                </button>
            </div>

            {{-- Orders table with inline dropdown --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Tracking #</th>
                            <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Dispatched</th>
                            <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($this->orders as $index => $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" wire:key="order-{{ $order->id }}">
                                <td class="px-4 py-3 text-gray-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-mono font-semibold text-gray-900 dark:text-white">
                                    {{ $order->tracking_number }}
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $order->dispatched_at?->format('M d, H:i') ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        x-on:change="$wire.updateStatus({{ $order->id }}, $el.value)"
                                        class="rounded-lg text-sm font-medium px-3 py-2 border cursor-pointer appearance-auto
                                            {{ match($order->status->value) {
                                                'delivered' => 'bg-green-50 border-green-300 text-green-800',
                                                'rejected' => 'bg-red-50 border-red-300 text-red-800',
                                                'delayed' => 'bg-yellow-50 border-yellow-300 text-yellow-800',
                                                'dispatched' => 'bg-blue-50 border-blue-300 text-blue-800',
                                                'returned' => 'bg-purple-50 border-purple-300 text-purple-800',
                                                default => 'bg-gray-50 border-gray-300 text-gray-800'
                                            } }}
                                            dark:bg-gray-900 dark:text-white dark:border-gray-600"
                                    >
                                        <option value="dispatched" @selected($order->status->value === 'dispatched')>Dispatched</option>
                                        <option value="delivered" @selected($order->status->value === 'delivered')>Delivered</option>
                                        <option value="rejected" @selected($order->status->value === 'rejected')>Rejected</option>
                                        <option value="delayed" @selected($order->status->value === 'delayed')>Delayed</option>
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    No orders found for this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-400">Showing {{ $this->orders->count() }} orders. Change the status directly from the dropdown — it saves automatically.</p>
        @else
            <div class="text-center py-8 text-gray-500">
                Select a company to view and update order statuses.
            </div>
        @endif
    </div>
</x-filament-panels::page>
