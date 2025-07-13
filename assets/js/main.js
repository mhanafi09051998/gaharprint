document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('file-input');
    const filePreview = document.getElementById('file-preview');
    const uploadForm = document.getElementById('upload-form');
    const historyTableBody = document.getElementById('history-table-body');
    const whatsappInput = document.getElementById('whatsapp_number');

    const MAX_FILES = 1000;
    const MAX_TOTAL_SIZE_MB = 500;
    const MAX_TOTAL_SIZE_BYTES = MAX_TOTAL_SIZE_MB * 1024 * 1024;
    let fileStore = new DataTransfer();

    // Function to render the file previews
    function renderPreviews() {
        filePreview.innerHTML = '';
        let totalSize = 0;

        if (fileStore.files.length === 0) {
            fileInput.value = ''; // Ensure input is cleared if no files are left
            return;
        }

        Array.from(fileStore.files).forEach((file, index) => {
            totalSize += file.size;
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    previewItem.appendChild(img);
                };
                reader.readAsDataURL(file);
            } else {
                const icon = document.createElement('div');
                icon.className = 'file-icon';
                icon.textContent = '📄';
                previewItem.appendChild(icon);
            }

            const fileName = document.createElement('span');
            fileName.textContent = file.name;
            previewItem.appendChild(fileName);

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-file-btn';
            removeBtn.textContent = 'x';
            removeBtn.type = 'button'; // Prevent form submission
            removeBtn.onclick = () => removeFile(index);
            previewItem.appendChild(removeBtn);

            filePreview.appendChild(previewItem);
        });

        // Final validation check after rendering
        if (totalSize > MAX_TOTAL_SIZE_BYTES) {
            alert(`Error: Ukuran total file tidak boleh melebihi ${MAX_TOTAL_SIZE_MB} MB.`);
            fileStore.clearData();
            renderPreviews();
        }
    }

    // Function to remove a file by its index
    function removeFile(index) {
        const newFiles = new DataTransfer();
        const currentFiles = Array.from(fileStore.files);
        currentFiles.splice(index, 1); // Remove the file at the specified index
        currentFiles.forEach(file => newFiles.items.add(file));
        fileStore = newFiles;
        renderPreviews();
    }

    // Handle file input changes
    fileInput.addEventListener('change', () => {
        const newFiles = Array.from(fileInput.files);
        let currentFileCount = fileStore.files.length;

        if (currentFileCount + newFiles.length > MAX_FILES) {
            alert(`Error: Anda hanya dapat mengunggah maksimal ${MAX_FILES} file.`);
            fileInput.value = ''; // Clear the new selection
            return;
        }

        newFiles.forEach(file => fileStore.items.add(file));
        renderPreviews();
    });

    // Handle form submission with AJAX
    uploadForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        submitButton.textContent = 'Mengunggah...';
        submitButton.disabled = true;

        const formData = new FormData();
        formData.append('order_number', document.getElementById('order_number').value);
        formData.append('whatsapp_number', whatsappInput.value);
        Array.from(fileStore.files).forEach(file => {
            formData.append('files[]', file);
        });

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.status === 'success') {
                uploadForm.reset();
                fileStore.clearData();
                renderPreviews();
                loadHistory(whatsappInput.value.trim());
            }
        })
        .catch(error => {
            console.error('Error during upload:', error);
            alert('Terjadi kesalahan teknis saat mengunggah.');
        })
        .finally(() => {
            submitButton.textContent = originalButtonText;
            submitButton.disabled = false;
        });
    });

    // Function to fetch and display history
    function loadHistory(whatsappNumber) {
        if (!whatsappNumber || whatsappNumber.length <= 8) {
            historyTableBody.innerHTML = '<tr><td colspan="6">Masukkan nomor WhatsApp yang valid untuk melihat riwayat.</td></tr>';
            return;
        }
        
        historyTableBody.innerHTML = '<tr><td colspan="6">Memuat riwayat...</td></tr>';

        fetch(`history.php?whatsapp_number=${whatsappNumber}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                historyTableBody.innerHTML = '';
                if (data.length === 0) {
                    historyTableBody.innerHTML = '<tr><td colspan="6">Tidak ada riwayat unggahan untuk nomor ini.</td></tr>';
                    return;
                }
                data.forEach((row, index) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${row.order_number}</td>
                        <td>+62${row.whatsapp_number}</td>
                        <td>${row.file_count}</td>
                        <td>${row.upload_date}</td>
                        <td>${row.status}</td>
                    `;
                    historyTableBody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Error fetching history:', error);
                historyTableBody.innerHTML = '<tr><td colspan="6">Gagal memuat riwayat. Silakan coba lagi.</td></tr>';
            });
    }

    // Load history when the user types their WhatsApp number
    let debounceTimer;
    whatsappInput.addEventListener('keyup', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const number = whatsappInput.value.trim();
            if (number.length > 8) { // Simple validation
                loadHistory(number);
            }
        }, 500); // Wait 500ms after user stops typing
    });

    // Initial load
    loadHistory(whatsappInput.value.trim());
});