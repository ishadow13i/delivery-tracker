<x-filament-panels::page>
    @if(!$selectedCompanyId)
        {{-- Company selection --}}
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">اختر شركة التوصيل</h2>
            <p class="text-sm text-gray-500">اختر شركة التوصيل التي تريد استيراد PDF الخاص بها.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->companies as $company)
                    <button
                        wire:click="selectCompany({{ $company->id }})"
                        class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:shadow-md transition-all text-left"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                <x-heroicon-o-building-office class="w-7 h-7 text-primary-600" />
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-lg text-gray-900 dark:text-white">{{ $company->name }}</p>
                                <p class="text-sm text-gray-500">اضغط لرفع ملف PDF</p>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>

            @if($this->companies->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    لا توجد شركات توصيل نشطة. أضف شركة من صفحة شركات التوصيل أولاً.
                </div>
            @endif
        </div>
    @else
        {{-- Upload form for selected company --}}
        @php $company = \App\Models\Company::find($selectedCompanyId); @endphp

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <button wire:click="backToCompanySelection" class="text-sm text-primary-600 hover:underline mb-2 inline-block">
                        &larr; تغيير الشركة
                    </button>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        استيراد لصالح: {{ $company->name }}
                    </h2>
                </div>
            </div>

            @if($processing)
                {{-- Processing state with polling --}}
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-primary-500 p-8 text-center space-y-4"
                    wire:poll.2s="pollExtraction"
                >
                    <div class="flex justify-center">
                        <svg class="animate-spin h-12 w-12 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $processingMessage ?: 'جاري المعالجة...' }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        قد يستغرق هذا حتى دقيقتين للملفات الكبيرة. لا تغلق الصفحة.
                    </p>
                </div>
            @elseif(!$showPreview)
                {{-- Step 1: Upload PDF --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <h3 class="font-medium text-gray-900 dark:text-white">الخطوة 1: اختر الدُفعة</h3>

                    <div class="flex items-center gap-3 mb-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model.live="useExistingBatch" class="rounded border-gray-300 text-primary-600">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">استخدام دُفعة موجودة</span>
                        </label>
                    </div>

                    @if($useExistingBatch)
                        <select wire:model="existingBatchId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">اختر دُفعة...</option>
                            @foreach($this->existingBatches as $batch)
                                <option value="{{ $batch->id }}">
                                    {{ $batch->name ?: "دُفعة #{$batch->id}" }} ({{ $batch->orders_count }} طلب) — {{ $batch->date->format('M d') }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input
                            type="text"
                            wire:model="batchName"
                            placeholder="اسم الدُفعة (اختياري، يعتمد على التاريخ)"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        >
                    @endif

                    <hr class="border-gray-200 dark:border-gray-700">

                    <h3 class="font-medium text-gray-900 dark:text-white">الخطوة 2: رفع ملف PDF</h3>
                    <p class="text-sm text-gray-500">
                        ارفع ملف PDF من {{ $company->name }} الذي يحتوي على كل ملصقات الطلبات.
                    </p>

                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
                        <input
                            type="file"
                            wire:model="pdfFile"
                            accept="application/pdf"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                        >

                        @if($pdfFile)
                            <p class="mt-3 text-sm text-green-600">
                                ✓ {{ $pdfFile->getClientOriginalName() }} ({{ round($pdfFile->getSize() / 1024) }} كيلوبايت)
                            </p>
                        @endif
                    </div>

                    <div wire:loading wire:target="pdfFile" class="text-sm text-gray-500">
                        جاري الرفع...
                    </div>

                    <x-filament::button
                        wire:click="extractFromPdf"
                        wire:loading.attr="disabled"
                        wire:target="extractFromPdf"
                        :disabled="!$pdfFile"
                        size="lg"
                        icon="heroicon-o-document-magnifying-glass"
                    >
                        <span wire:loading.remove wire:target="extractFromPdf">استخراج أرقام التتبع</span>
                        <span wire:loading wire:target="extractFromPdf">جاري الاستخراج...</span>
                    </x-filament::button>
                </div>
            @else
                {{-- Step 2: Preview extracted numbers with categorization --}}
                @php
                    $newCount = count($newNumbers);
                    $updatableCount = count($updatableNumbers);
                    $duplicateCount = count($duplicateNumbers);
                    $totalToImport = $newCount + $updatableCount;
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-gray-900 dark:text-white">
                            معاينة — تم العثور على {{ count($extractedNumbers) }} رقم تتبع
                        </h3>
                        <button wire:click="cancelPreview" class="text-sm text-gray-500 hover:underline">
                            إلغاء وإعادة الرفع
                        </button>
                    </div>

                    {{-- Summary breakdown --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-plus-circle class="w-5 h-5 text-green-600" />
                                <p class="text-sm font-medium text-green-800 dark:text-green-200">جديدة</p>
                            </div>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">{{ $newCount }}</p>
                            <p class="text-xs text-green-600 dark:text-green-400">سيتم إنشاؤها</p>
                        </div>

                        <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-arrow-path class="w-5 h-5 text-blue-600" />
                                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">قيد الانتظار</p>
                            </div>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ $updatableCount }}</p>
                            <p class="text-xs text-blue-600 dark:text-blue-400">سيتم تحديثها إلى مُرسلة</p>
                        </div>

                        <div class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600" />
                                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">مكررة</p>
                            </div>
                            <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300 mt-1">{{ $duplicateCount }}</p>
                            <p class="text-xs text-yellow-600 dark:text-yellow-400">سيتم تجاهلها</p>
                        </div>
                    </div>

                    {{-- Warning if all duplicates --}}
                    @if($totalToImport === 0)
                        <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700">
                            <div class="flex items-start gap-2">
                                <x-heroicon-o-no-symbol class="w-6 h-6 text-red-600 flex-shrink-0" />
                                <div>
                                    <p class="font-semibold text-red-800 dark:text-red-200">لا يوجد شيء جديد للاستيراد</p>
                                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                        كل الأرقام الـ{{ count($extractedNumbers) }} موجودة بالفعل في النظام. هل أنت متأكد من أنك رفعت الملف الصحيح؟
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Categorized number lists --}}
                    <div class="space-y-3">
                        @if($newCount > 0)
                            <details class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700" open>
                                <summary class="cursor-pointer px-4 py-2 font-medium text-sm text-green-700 dark:text-green-400">
                                    جديدة ({{ $newCount }})
                                </summary>
                                <div class="px-4 pb-3 max-h-64 overflow-y-auto">
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 pt-2">
                                        @foreach($newNumbers as $num)
                                            <span class="font-mono text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300">
                                                {{ $num }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif

                        @if($duplicateCount > 0)
                            <details class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                                <summary class="cursor-pointer px-4 py-2 font-medium text-sm text-yellow-700 dark:text-yellow-400">
                                    مكررة — تجاهل ({{ $duplicateCount }})
                                </summary>
                                <div class="px-4 pb-3 max-h-64 overflow-y-auto">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pt-2">
                                        @foreach($duplicateNumbers as $item)
                                            <span class="font-mono text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300">
                                                {{ $item['tracking'] }} <span class="text-gray-500">(بالفعل {{ $item['currentStatus'] }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <x-filament::button
                            wire:click="confirmImport"
                            wire:loading.attr="disabled"
                            wire:target="confirmImport"
                            :disabled="$totalToImport === 0"
                            color="success"
                            size="lg"
                            icon="heroicon-o-check-circle"
                        >
                            <span wire:loading.remove wire:target="confirmImport">
                                @if($totalToImport === 0)
                                    لا شيء للاستيراد
                                @else
                                    تأكيد واستيراد {{ $totalToImport }} طلب
                                @endif
                            </span>
                            <span wire:loading wire:target="confirmImport">جاري المعالجة...</span>
                        </x-filament::button>

                        <x-filament::button
                            wire:click="cancelPreview"
                            color="gray"
                            size="lg"
                        >
                            إلغاء
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
