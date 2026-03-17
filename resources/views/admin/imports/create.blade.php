@extends('layouts.admin')
@section('title', 'Upload CSV')
@section('page-title', 'Upload CSV File')

@section('content')
<div class="py-6">
<div class="max-w-2xl">

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
        <h3 class="text-blue-800 font-semibold text-sm flex items-center gap-2 mb-3"><i class="fas fa-info-circle"></i> CSV Format Guide</h3>
        <p class="text-blue-700 text-sm mb-3">Your CSV file should include the following columns (required are bold):</p>
        <div class="grid grid-cols-2 gap-2">
            @foreach([
                ['title', 'Product name', true],
                ['price', 'Product price', true],
                ['sku', 'Stock keeping unit', false],
                ['vendor', 'Brand/Vendor', false],
                ['type', 'Product type/category', false],
                ['description', 'Product description (HTML)', false],
                ['tags', 'Comma-separated tags', false],
                ['quantity', 'Inventory quantity', false],
                ['image_src', 'Product image URL', false],
                ['compare_at_price', 'Original price for sale', false],
                ['weight', 'Product weight', false],
                ['handle', 'URL handle', false],
            ] as [$col, $desc, $req])
            <div class="flex items-start gap-2 text-xs">
                <code class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-mono {{ $req ? 'ring-1 ring-blue-400' : '' }}">{{ $col }}{{ $req ? '*' : '' }}</code>
                <span class="text-blue-600">{{ $desc }}</span>
            </div>
            @endforeach
        </div>
        <p class="text-blue-500 text-xs mt-3">* Required fields. First row must be the column header row.</p>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-violet-600 to-purple-700 px-6 py-5">
            <h2 class="text-white font-bold text-lg"><i class="fas fa-upload mr-2"></i>Upload CSV File</h2>
            <p class="text-purple-200 text-sm">Products will be imported to your Shopify store asynchronously.</p>
        </div>
        <form action="{{ route('admin.imports.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf

            <!-- Batch Name -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Batch Name <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="batch_name" value="{{ old('batch_name') }}"
                       placeholder="e.g. Summer Collection 2024"
                       class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent @error('batch_name') border-red-400 @enderror">
                @error('batch_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-gray-400 text-xs mt-1">Leave blank to use the filename as the batch name.</p>
            </div>

            <!-- File Upload -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">CSV File <span class="text-red-500">*</span></label>
                <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-violet-400 hover:bg-violet-50/50 transition-all duration-200 @error('csv_file') border-red-400 @enderror">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-300 mb-3 block" id="upload-icon"></i>
                    <p class="text-gray-600 font-medium text-sm" id="upload-text">Drag & drop your CSV file here</p>
                    <p class="text-gray-400 text-xs mt-1 mb-4">or click to browse</p>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" class="hidden" required>
                    <label for="csv_file" class="inline-block bg-violet-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg cursor-pointer hover:bg-violet-700 transition-colors">
                        Browse Files
                    </label>
                    <p id="file-name-display" class="text-violet-600 text-sm font-medium mt-3 hidden"></p>
                </div>
                @error('csv_file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-gray-400 text-xs mt-1">Accepted formats: .csv, .txt &bull; Maximum size: 50MB</p>
            </div>

            <!-- Info -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-xs text-gray-500 space-y-1">
                <p class="flex items-center gap-2"><i class="fas fa-store text-violet-500"></i> <strong>Shopify Store:</strong> laravel-import-test.myshopify.com</p>
                <p class="flex items-center gap-2"><i class="fas fa-tags text-violet-500"></i> <strong>Collection:</strong> All products will be assigned to Collection ID 464337174767</p>
                <p class="flex items-center gap-2"><i class="fas fa-cogs text-violet-500"></i> <strong>Processing:</strong> Import runs in the background queue. Progress is tracked in real-time.</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-gradient-to-r from-violet-600 to-purple-700 hover:from-violet-700 hover:to-purple-800 text-white font-semibold px-8 py-3 rounded-lg transition-all flex items-center gap-2 shadow-md hover:shadow-lg">
                    <i class="fas fa-cloud-upload-alt"></i> Start Import
                </button>
                <a href="{{ route('admin.imports.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <!-- Sample CSV Download -->
    <div class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm">
        <p class="font-semibold text-amber-800 flex items-center gap-2"><i class="fas fa-file-download"></i> Sample CSV Template</p>
        <p class="text-amber-700 text-xs mt-1">Use this as a guide for your CSV structure:</p>
        <code class="block mt-2 bg-amber-100 text-amber-900 p-3 rounded text-xs font-mono overflow-x-auto whitespace-nowrap">title,price,sku,vendor,type,description,tags,quantity,image_src<br>Classic T-Shirt,29.99,TSHIRT-001,My Brand,Apparel,&quot;A great t-shirt&quot;,"fashion,apparel",50,https://example.com/img.jpg</code>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
const fileInput = document.getElementById('csv_file');
const dropZone  = document.getElementById('drop-zone');
const uploadText = document.getElementById('upload-text');
const fileNameDisplay = document.getElementById('file-name-display');
const uploadIcon = document.getElementById('upload-icon');

fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        const name = this.files[0].name;
        const size = (this.files[0].size / 1024).toFixed(1);
        uploadText.textContent = 'File selected';
        fileNameDisplay.textContent = name + ' (' + size + ' KB)';
        fileNameDisplay.classList.remove('hidden');
        uploadIcon.className = 'fas fa-check-circle text-4xl text-green-400 mb-3 block';
        dropZone.classList.add('border-green-400', 'bg-green-50/50');
        dropZone.classList.remove('border-gray-300');
    }
});

dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-violet-500', 'bg-violet-50');
});

dropZone.addEventListener('dragleave', function() {
    this.classList.remove('border-violet-500', 'bg-violet-50');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-violet-500', 'bg-violet-50');
    const dt = e.dataTransfer;
    if (dt.files.length > 0) {
        fileInput.files = dt.files;
        fileInput.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
