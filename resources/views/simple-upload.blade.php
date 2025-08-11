<div class="p-4">
    <h3 class="text-lg font-semibold mb-4">Upload SQL Backup File</h3>
    
    <div id="upload-form">
        <form id="simple-upload-form" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Select SQL file (max 200MB)
                </label>
                <input type="file" 
                       id="sql-file" 
                       name="file" 
                       accept=".sql"
                       class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600">
            </div>
            
            <div id="file-info" class="mb-4 hidden">
                <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded">
                    <p class="text-sm">
                        <strong>File:</strong> <span id="file-name"></span><br>
                        <strong>Size:</strong> <span id="file-size"></span> MB
                    </p>
                </div>
            </div>
            
            <div id="upload-progress" class="mb-4 hidden">
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">Uploading... <span id="progress-text">0%</span></p>
            </div>
            
            <div id="upload-result" class="mb-4 hidden">
                <div class="bg-green-100 dark:bg-green-900 p-4 rounded">
                    <p class="text-green-800 dark:text-green-200">
                        ✅ File uploaded successfully!<br>
                        <span id="result-message"></span>
                    </p>
                </div>
            </div>
            
            <div id="upload-error" class="mb-4 hidden">
                <div class="bg-red-100 dark:bg-red-900 p-4 rounded">
                    <p class="text-red-800 dark:text-red-200">
                        ❌ Upload failed!<br>
                        <span id="error-message"></span>
                    </p>
                </div>
            </div>
            
            <button type="submit" 
                    id="upload-button"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                Upload File
            </button>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('sql-file');
            const uploadButton = document.getElementById('upload-button');
            const form = document.getElementById('simple-upload-form');
            
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    document.getElementById('file-name').textContent = file.name;
                    document.getElementById('file-size').textContent = (file.size / 1024 / 1024).toFixed(2);
                    document.getElementById('file-info').classList.remove('hidden');
                    uploadButton.disabled = false;
                } else {
                    document.getElementById('file-info').classList.add('hidden');
                    uploadButton.disabled = true;
                }
            });
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const file = fileInput.files[0];
                if (!file) return;
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                
                // Hide form elements
                document.getElementById('file-info').classList.add('hidden');
                uploadButton.disabled = true;
                
                // Show progress
                document.getElementById('upload-progress').classList.remove('hidden');
                document.getElementById('upload-error').classList.add('hidden');
                document.getElementById('upload-result').classList.add('hidden');
                
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        document.getElementById('progress-bar').style.width = percentComplete + '%';
                        document.getElementById('progress-text').textContent = Math.round(percentComplete) + '%';
                    }
                });
                
                xhr.addEventListener('load', function() {
                    document.getElementById('upload-progress').classList.add('hidden');
                    
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        document.getElementById('result-message').textContent = 
                            `File: ${response.filename}. Click "Refresh List" and then use "Select Existing Backup" to restore it.`;
                        document.getElementById('upload-result').classList.remove('hidden');
                        
                        // Dispatch event to refresh backup list
                        setTimeout(() => {
                            window.dispatchEvent(new CustomEvent('refresh-backups'));
                        }, 1000);
                    } else {
                        document.getElementById('error-message').textContent = 
                            'Upload failed. Server returned status: ' + xhr.status;
                        document.getElementById('upload-error').classList.remove('hidden');
                        uploadButton.disabled = false;
                    }
                });
                
                xhr.addEventListener('error', function() {
                    document.getElementById('upload-progress').classList.add('hidden');
                    document.getElementById('error-message').textContent = 
                        'Upload failed. Network error or server not responding.';
                    document.getElementById('upload-error').classList.remove('hidden');
                    uploadButton.disabled = false;
                });
                
                xhr.open('POST', '{{ route("simple.upload") }}');
                xhr.send(formData);
            });
        });
    </script>
</div>