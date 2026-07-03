<x-filament-panels::page>
    @if(!$selectedBatchId)
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">اختر دُفعة للمسح</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->batches as $batch)
                    <button
                        wire:click="selectBatch({{ $batch->id }})"
                        class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-md transition-all text-left"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $batch->name ?: "دُفعة #{$batch->id}" }}
                                </p>
                                <p class="text-sm text-gray-500">{{ $batch->company->name }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $batch->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-primary-600">{{ $batch->orders_count }}</span>
                                <p class="text-xs text-gray-400">طلب</p>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>

            @if($this->batches->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    لا توجد دُفعات. أنشئ دُفعة من صفحة الدُفعات أولاً.
                </div>
            @endif
        </div>
    @else
        <div class="space-y-6">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <button wire:click="$set('selectedBatchId', null)" class="text-sm text-primary-600 hover:underline mb-2 inline-block">
                        &larr; العودة إلى قائمة الدُفعات
                    </button>
                    @php $batch = \App\Models\Batch::with('company')->find($selectedBatchId); @endphp
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $batch?->name ?: "دُفعة #{$selectedBatchId}" }} — {{ $batch?->company?->name }}
                    </h2>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-bold text-primary-600">{{ $scannedCount }}</span>
                    <p class="text-sm text-gray-400">طلب تم مسحه</p>
                </div>
            </div>

            {{-- Scan input --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-primary-500 p-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    امسح الباركود — يتم إنشاء الطلبات تلقائياً
                </label>
                <input
                    type="text"
                    autofocus
                    autocomplete="off"
                    class="w-full text-2xl p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:text-white"
                    placeholder="بانتظار مسح الباركود..."
                    x-data="{}"
                    x-init="$el.focus()"
                    x-on:keydown.enter.prevent="
                        if ($el.value.trim() !== '') {
                            $wire.barcodeInput = $el.value;
                            $wire.scan();
                            $el.value = '';
                        }
                    "
                    @scan-success.window="$el.focus()"
                    @scan-error.window="$el.focus()"
                >
            </div>

            {{-- Scan results feed --}}
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($scanResults as $result)
                    <div class="flex items-center gap-3 p-3 rounded-lg {{ $result['success'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                        @if($result['success'])
                            <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 flex-shrink-0" />
                        @else
                            <x-heroicon-o-x-circle class="w-6 h-6 text-red-600 flex-shrink-0" />
                        @endif
                        <div class="flex-1">
                            <span class="font-mono font-semibold {{ $result['success'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                {{ $result['barcode'] }}
                            </span>
                            <span class="text-sm {{ $result['success'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                — {{ $result['message'] }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $result['time'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
