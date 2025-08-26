document.addEventListener('DOMContentLoaded', () => {
    const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const targetPathInput = document.getElementById('targetPath');
    const progressBar = document.getElementById('progressBar');
    const statusDiv = document.getElementById('status');

    if (!uploadForm || !fileInput || !targetPathInput || !progressBar || !statusDiv) {
        console.error('Required DOM elements not found');
        return;
    }

    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const file = fileInput.files[0];
        if (!file) {
            statusDiv.textContent = 'Please select a file.';
            return;
        }

        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        let uploadedChunks = 0;

        statusDiv.textContent = 'Starting upload...';
        progressBar.value = 0;
        progressBar.max = totalChunks;

        // Check if OC is available
        if (typeof OC === 'undefined' || typeof OC.generateUrl !== 'function') {
            statusDiv.textContent = 'Nextcloud environment not loaded. Please refresh the page.';
            return;
        }

        for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunk = file.slice(start, end);

            const formData = new FormData();
            formData.append('fileName', file.name);
            formData.append('chunkIndex', i);
            formData.append('totalChunks', totalChunks);
            formData.append('chunk', chunk);

            try {
                const response = await fetch(OC.generateUrl('/apps/largefileupload/upload/chunk'), {
                    method: 'POST',
                    body: formData,
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();

                if (result.status === 'chunk_uploaded') {
                    uploadedChunks++;
                    progressBar.value = uploadedChunks;
                    statusDiv.textContent = `Uploaded chunk ${uploadedChunks} of ${totalChunks}`;
                } else {
                    statusDiv.textContent = `Error uploading chunk ${i}: ${result.error || 'Unknown error'}`;
                    return;
                }
            } catch (error) {
                statusDiv.textContent = `Error uploading chunk ${i}: ${error.message}`;
                return;
            }
        }

        // Assemble the file
        const formData = new FormData();
        formData.append('fileName', file.name);
        formData.append('totalChunks', totalChunks);
        formData.append('targetPath', targetPathInput.value || ''); // Default to empty if not set

        try {
            const response = await fetch(OC.generateUrl('/apps/largefileupload/upload/assemble'), {
                method: 'POST',
                body: formData,
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.status === 'file_assembled') {
                statusDiv.textContent = `File uploaded successfully to ${result.path || 'unknown location'}`;
                progressBar.value = totalChunks;
            } else {
                statusDiv.textContent = `Error assembling file: ${result.error || 'Unknown error'}`;
            }
        } catch (error) {
            statusDiv.textContent = `Error assembling file: ${error.message}`;
        }
    });
});