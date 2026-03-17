@extends('layouts.admin')

@section('title', 'Upload CSV')
@section('page-title', 'Upload CSV File')
@section('page-subtitle', 'Import products from a CSV file into Shopify')

@section('content')
<div class="max-w-2xl pt-2">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">

        <form id="uploadForm" method="POST" action="{{ route('admin.uploads.store') }}" enctype="multipart/form-data">
            @csrf

            <!-- Drag & Drop Zone -->
            <div id="dropZone"
                 class="relative border-2 border-dashed border-gray-300 rounded-xl p-10 text-center cursor-pointer
                        hover:border-indigo-400 hover:bg-indigo-50 transition-all duration-200"
                 onclick="document.getElementById('csv_file').click()">

                <div id="dropZoneDefault">
                    <i class="fa-solid fa-cloud-arrow-up text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 font-medium text-lg">Drag & drop your CSV file here</p>
                    <p class="text-gray-400 text-sm mt-1">or <span class="text-indigo-600 font-medium">browse to select</span></p>
                    <p class="text-gray-400 text-xs mt-3">Supported: <strong>.csv</strong> · Max size: <strong>5MB</strong></p>
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
                <p class="text-red-600 text-sm mt-2"><i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $message }}</p>
            @enderror
            <p id="fileError" class="text-red-600 text-sm mt-2 hidden"></p>

            <!-- Shopify Collection ID -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Shopify Collection ID
                    <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <div class="relative">
                    <i class="fa-solid fa-layer-group absolute left-3 top-3 text-gray-400 text-sm"></i>
                    <input type="text" name="collection_id"
                           value="{{ old('collection_id', config('shopify.default_collection_id')) }}"
                           placeholder="e.g. 123456789 or gid://shopify/Collection/123456789"
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
                </div>
                <p class="text-xs text-gray-400 mt-1.5">Products will be assigned to this Shopify collection after import. Leave blank to skip collection assignment.</p>
                @error('collection_id')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- CSV Format Guide -->
            <div class="mt-6 bg-indigo-50 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-indigo-800 mb-3">
                    <i class="fa-solid fa-circle-info mr-1.5"></i>Expected CSV Column Headers
                </h3>
                <div class="grid grid-cols-2 gap-2">
                    @php
                        $columns = [
                            ['title', 'Required'],
                            ['price', 'Required'],
                            ['description', 'Optional'],
                            ['sku', 'Optional'],
                            ['vendor', 'Optional'],
                            ['product_type', 'Optional'],
                            ['tags', 'Optional'],
                            ['compare_at_price', 'Optional'],
                            ['inventory_quantity', 'Optional'],
                        ];
                    @endphp
                    @foreach($columns as [$col, $req])
                    <div class="flex items-center space-x-2">
                        <code class="text-xs bg-white border border-indigo-200 text-indigo-700 px-2 py-0.5 rounded font-mono">{{ $col }}</code>
                        <span class="text-xs {{ $req === 'Required' ? 'text-indigo-600 font-semibold' : 'text-gray-400' }}">{{ $req }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-indigo-200">
                    <p class="text-xs font-semibold text-indigo-700 mb-2">Sample Row:</p>
                    <div class="bg-white rounded-lg p-3 overflow-x-auto">
                        <pre class="text-xs text-gray-600 font-mono">title,price,sku,description,vendor
Premium T-Shirt,29.99,TSH-001,Soft cotton tee,Nike
Blue Denim Jeans,79.95,JNS-002,Slim fit jeans,Levi's</pre>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-indigo-200">
                    <p class="text-xs text-indigo-700">
                        <i class="fa-solid fa-circle-check mr-1"></i>
                        <strong>Duplicate detection:</strong> Products are matched by SKU (preferred) or title. Existing products will be updated instead of duplicated.
                    </p>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center space-x-3 mt-6">
                <button type="submit" id="submitBtn"
                        class="inline-flex items-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-lg transition-all duration-200 text-sm">
                    <i class="fa-solid fa-upload"></i>
                    <span>Start Import</span>
                </button>
                <a href="{{ route('admin.uploads.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
const MAX_BYTES = 5 * 1024 * 1024;

function handleFileSelect(input) {
    const file       = input.files[0];
    const errorEl    = document.getElementById('fileError');
    const defaultEl  = document.getElementById('dropZoneDefault');
    const selectedEl = document.getElementById('dropZoneSelected');

    errorEl.classList.add('hidden');

    if (!file) { defaultEl.classList.remove('hidden'); selectedEl.classList.add('hidden'); return; }

    if (!file.name.toLowerCase().endsWith('.csv')) {
        errorEl.textContent = 'Invalid file type. Only .csv files are accepted.';
        errorEl.classList.remove('hidden');
        input.value = ''; defaultEl.classList.remove('hidden'); selectedEl.classList.add('hidden');
        return;
    }

    if (file.size > MAX_BYTES) {
        errorEl.textContent = `File too large (${(file.size/1024/1024).toFixed(2)}MB). Max allowed is 5MB.`;
        errorEl.classList.remove('hidden');
        input.value = ''; defaultEl.classList.remove('hidden'); selectedEl.classList.add('hidden');
        return;
    }

    const size = file.size < 1048576 ? (file.size/1024).toFixed(1)+' KB' : (file.size/1048576).toFixed(2)+' MB';
    document.getElementById('selectedFileName').textContent = file.name;
    document.getElementById('selectedFileSize').textContent  = size;
    defaultEl.classList.add('hidden');
    selectedEl.classList.remove('hidden');
}

const dz = document.getElementById('dropZone');
dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('border-indigo-500', 'bg-indigo-50'); });
dz.addEventListener('dragleave', ()  => dz.classList.remove('border-indigo-500', 'bg-indigo-50'));
dz.addEventListener('drop', e => {
    e.preventDefault(); dz.classList.remove('border-indigo-500', 'bg-indigo-50');
    const inp = document.getElementById('csv_file');
    inp.files = e.dataTransfer.files;
    handleFileSelect(inp);
});
</script>
@endsection
