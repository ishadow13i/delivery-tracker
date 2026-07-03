<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">مُرتجعة اليوم</p>
                <p class="text-3xl font-bold text-primary-600">{{ $returnedToday }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">مرفوضة (بانتظار الإرجاع)</p>
                <p class="text-3xl font-bold text-red-600">
                    {{ \App\Models\Order::where('status', 'rejected')->count() }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <p class="text-sm text-gray-500">تم مسحها في هذه الجلسة</p>
                <p class="text-3xl font-bold text-green-600">{{ count($scanResults) }}</p>
            </div>
        </div>

        {{-- Scan input --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-orange-500 p-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                امسح باركود الطرد المُرتجع
            </label>
            <input
                type="text"
                autofocus
                autocomplete="off"
                class="w-full text-2xl p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-900 dark:text-white"
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
        <div class="space-y-2 max-h-64 overflow-y-auto">
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

        {{-- Skipped / Partial Return Notes --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-yellow-400 p-6">
            <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-3">
                ملاحظة تخطي (إرجاع جزئي / مشكلة)
            </h3>
            <p class="text-sm text-gray-500 mb-4">
                إذا كان الطرد يحتوي على قطع ناقصة ولا تريد مسحه، سجّل ملاحظة هنا.
            </p>
            <form wire:submit="saveNote" class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <input
                            type="text"
                            wire:model="noteTrackingNumber"
                            class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white"
                            placeholder="رقم التتبع (اكتب أو امسح)"
                        >
                    </div>
                    <div class="md:col-span-2">
                        <input
                            type="text"
                            wire:model="noteText"
                            class="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-900 dark:text-white"
                            placeholder="مثال: تم إرجاع 2 من 3 قطع، ناقص قطعة واحدة"
                        >
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 font-medium text-sm">
                    حفظ الملاحظة
                </button>
            </form>

            {{-- Saved notes this session --}}
            @if(!empty($skippedNotes))
                <div class="mt-4 space-y-2">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">ملاحظات هذه الجلسة:</h4>
                    @foreach($skippedNotes as $note)
                        <div class="flex items-center gap-3 p-2 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                            <x-heroicon-o-document-text class="w-5 h-5 text-yellow-600 flex-shrink-0" />
                            <div class="flex-1">
                                <span class="font-mono font-semibold text-yellow-800 dark:text-yellow-200">{{ $note['tracking'] }}</span>
                                <span class="text-sm text-yellow-700 dark:text-yellow-300"> — {{ $note['note'] }}</span>
                                @if(!$note['found'])
                                    <span class="text-xs text-red-500 ml-1">(غير موجود في النظام)</span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400">{{ $note['time'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
