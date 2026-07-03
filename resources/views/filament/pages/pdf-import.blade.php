<x-filament-panels::page>
    @if(!$selectedCompanyId)
        {{-- Company selection --}}
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Select Delivery Company</h2>
            <p class="text-sm text-gray-500">Choose the delivery company whose PDF you're importing.</p>

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
                                <p class="text-sm text-gray-500">Click to upload PDF</p>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>

            @if($this->companies->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    No active delivery companies. Add one from the Companies page first.
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
                        &larr; Change company
                    </button>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Importing for: {{ $company->name }}
                    </h2>
                </div>
            </div>

            @if(!$showPreview)
                {{-- Step 1: Upload PDF --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <h3 class="font-medium text-gray-900 dark:text-white">Step 1: Choose Batch</h3>

                    <div class="flex items-center gap-3 mb-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model.live="useExistingBatch" class="rounded border-gray-300 text-primary-600">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Use existing batch</span>
                        </label>
                    </div>

                    @if($useExistingBatch)
                        <select wire:model="existingBatchId" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="">Select Batch...</option>
                            @foreach($this->existingBatches as $batch)
                                <option value="{{ $batch->id }}">
                                    {{ $batch->name ?: "Batch #{$batch->id}" }} ({{ $batch->orders_count }} orders) — {{ $batch->date->format('M d') }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input
                            type="text"
                            wire:model="batchName"
                            placeholder="Batch name (optional, defaults to date)"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        >
                    @endif

                    <hr class="border-gray-200 dark:border-gray-700">

                    <h3 class="font-medium text-gray-900 dark:text-white">Step 2: Upload PDF</h3>
                    <p class="text-sm text-gray-500">
                        Upload the PDF from {{ $company->name }} containing all order stickers.
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
                                ✓ {{ $pdfFile->getClientOriginalName() }} ({{ round($pdfFile->getSize() / 1024) }} KB)
                            </p>
                        @endif
                    </div>

                    <div wire:loading wire:target="pdfFile" class="text-sm text-gray-500">
                        Uploading...
                    </div>

                    <button
                        wire:click="extractFromPdf"
                        wire:loading.attr="disabled"
                        wire:target="extractFromPdf"
                        @disabled(!$pdfFile)
                        class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 font-medium"
                    >
                        <span wire:loading.remove wire:target="extractFromPdf">Extract Tracking Numbers</span>
                        <span wire:loading wire:target="extractFromPdf">Extracting...</span>
                    </button>
                </div>
            @else
                {{-- Step 2: Preview extracted numbers --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-medium text-gray-900 dark:text-white">
                            Preview — {{ count($extractedNumbers) }} tracking numbers found
                        </h3>
                        <button wire:click="cancelPreview" class="text-sm text-gray-500 hover:underline">
                            Cancel & Re-upload
                        </button>
                    </div>

                    <div class="max-h-96 overflow-y-auto bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                            @foreach($extractedNumbers as $num)
                                <span class="font-mono text-sm bg-white dark:bg-gray-800 px-2 py-1 rounded border border-gray-200 dark:border-gray-700">
                                    {{ $num }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <p class="text-sm text-gray-500">
                            Review the numbers above. Click "Confirm" to create orders.
                        </p>
                        <button
                            wire:click="confirmImport"
                            wire:loading.attr="disabled"
                            wire:target="confirmImport"
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 font-medium"
                        >
                            <span wire:loading.remove wire:target="confirmImport">
                                Confirm & Create {{ count($extractedNumbers) }} Orders
                            </span>
                            <span wire:loading wire:target="confirmImport">Creating...</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
