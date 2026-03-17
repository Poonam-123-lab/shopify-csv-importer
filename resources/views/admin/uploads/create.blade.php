@extends('layouts.admin')

@section('title', 'Upload CSV')
@section('page-title', 'Upload CSV File')
@section('page-subtitle', 'Import products from a CSV file into Shopify')

@section('content')
<div class="max-w-2xl pt-2">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">

        <!-- Upload Form -->
        <form id="uploadForm" method="POST" action="{{ route('admin.uploads.store') }}" enctype="multipart/form-data">
            @csrf

            <div id="dropZone"
                 class="relative border-2 border-dashed border-gray-300 rounded-xl p-10 text-center cursor-pointer
                        hover:border-indigo-400 hover:bg-indigo-50 transition-all duration-200"
                 onclick="document.getElementById('csv_file').click()">

                <div id="dropZoneDefault">
                    <i class="fa-solid fa-cloud-arrow-up text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 font-medium text-lg">Drag & drop your CSV file here</p>
                    <p class="text-gray-400 text-sm mt-1">or <span class="text-indigo-600 font-medium">browse to select</span></p>
                    <p class="text-gray-400 text-xs mt-3">Supported format: <strong>.csv</strong> — Maximum file size: <strong>5MB</strong></p>
                </div>

                <div id="dropZoneSelected" class="hidden">
                    <i class="fa-solid fa-file-csv text-5xl text-indigo-500 mb-4"></i>
                    <p id="selectedFileName" class="text-gray-800 font-semibold text-lg"></p>
                    <p id="selectedFileSize" class="text-gray-400 text-sm mt-1"></p>
                    <p class="text-indigo-500 text-xs mt-2">Click to change file</p>
                </div>

                <input type="file" id="csv_file" name="csv_file" accept=".csv"
                       class="sr-only" onchange="handleFileSelect(this)">
            </div>

            @error('csv_file')
                <p class="text-red-600 text-sm mt-2 flex items-center space-x-1">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span>{{ $message }}</span>
                </p>
            @enderror

            <!-- Client-side error message -->
            <p id="fileError" class="text-red-600 text-sm mt-2 hidden"></p>

            <!-- CSV Format Guide -->
            <div class="mt-6 bg-indigo-50 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-indigo-800 mb-3">
                    <i class="fa-solid fa-circle-info mr-1.5"></i>
                    Expected CSV Column Headers
                </h3>
                <div class="grid grid-cols-2 gap-2">
                    @php
                        $columns = [
                            ['title', 'Required', 'indigo'],
                            ['price', 'Required', 'indigo'],
                            ['description', 'Optional', 'gray'],
                            ['sku', 'Optional', 'gray'],
                            ['vendor', 'Optional', 'gray'],
                            ['product_type', 'Optional', 'gray'],
                            ['tags', 'Optional', 'gray'],
                            ['compare_at_price', 'Optional', 'gray'],
                            ['inventory_quantity', 'Optional', 'gray'],
                        ];
                    @endphp
                    @foreach($columns as [$col, $req, $color])
                    <div class="flex items-center space-x-2">
                        <code class="text-xs bg-white border border-indigo-200 text-indigo-700 px-2 py-0.5 rounded font-mono">{{ $col }}</code>
                        <span class="text-xs {{ $color === 'indigo' ? 'text-indigo-600 font-semibold' : 'text-gray-400' }}">{{ $req }}</span>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-4 border-t border-indigo-200">
                    <p class="text-xs font-semibold text-indigo-700 mb-2">Sample CSV Row:</p>
                    <div class="bg-white rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-gray-600 font-mono">title,price,description,sku,vendor
Premium T-Shirt,29.99,Soft cotton tee,TSH-001,Nike
Blue Denim Jeans,79.95,Slim fit jeans,JNS-002,Levi's</pre>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center space-x-3 mt-6">
                <button type="submit" id="submitBtn"
                        class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300
                               text-white font-semibold px-6 py-2.5 rounded-lg transition-all duration-200 text-sm">
                    <i class="fa-solid fa-upload"></i>
                    <span>Start Import</span>
                </button>
                <a href="{{ route('admin.uploads.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const MAX_SIZE_MB = 5;
const MAX_BYTES  = MAX_SIZE_MB * 1024 * 1024;

function handleFileSelect(input) {
    const file      = input.files[0];
    const errorEl   = document.getElementById('fileError');
    const defaultEl = document.getElementById('dropZoneDefault');
    const selectedEl= document.getElementById('dropZoneSelected');
    const submitBtn = document.getElementById('submitBtn');

    errorEl.classList.add('hidden');
    errorEl.textContent = '';

    if (!file) {
        defaultEl.classList.remove('hidden');
        selectedEl.classList.add('hidden');
        return;
    }

    // Validate type
    if (!file.name.toLowerCase().endsWith('.csv')) {
        errorEl.textContent = 'Invalid file type. Only .csv files are accepted.';
        errorEl.classList.remove('hidden');
        input.value = '';
        defaultEl.classList.remove('hidden');
        selectedEl.classList.add('hidden');
        return;
    }

    // Validate size
    if (file.size > MAX_BYTES) {
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        errorEl.textContent = `File is too large (${sizeMB}MB). Maximum allowed size is ${MAX_SIZE_MB}MB.`;
        errorEl.classList.remove('hidden');
        input.value = '';
        defaultEl.classList.remove('hidden');
        selectedEl.classList.add('hidden');
        return;
    }

    // Show selected file
    const sizeKB = file.size < 1024 * 1024
        ? (file.size / 1024).toFixed(1) + ' KB'
        : (file.size / 1024 / 1024).toFixed(2) + ' MB';

    document.getElementById('selectedFileName').textContent = file.name;
    document.getElementById('selectedFileSize').textContent  = sizeKB;
    defaultEl.classList.add('hidden');
    selectedEl.classList.remove('hidden');
}

// Drag and drop
const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-indigo-500', 'bg-indigo-50'); });
dropZone.addEventListener('dragleave', ()  => { dropZone.classList.remove('border-indigo-500', 'bg-indigo-50'); });
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-indigo-500', 'bg-indigo-50');
    const input = document.getElementById('csv_file');
    input.files = e.dataTransfer.files;
    handleFileSelect(input);
});
</script>
@endsection
