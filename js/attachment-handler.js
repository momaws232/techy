/**
 * Attachment Handler
 * 
 * This script handles attachment uploads and previews
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const attachmentInputs = document.querySelectorAll('input[type="file"][name="attachments[]"]');
    
    // Handle attachment previews
    attachmentInputs.forEach(input => {
        if (!input) return; // Skip if not found
        
        // Create preview container if it doesn't exist
        let previewContainer = document.getElementById('attachment-previews');
        if (!previewContainer) {
            previewContainer = document.createElement('div');
            previewContainer.id = 'attachment-previews';
            previewContainer.className = 'attachment-previews mt-2';
            input.parentNode.appendChild(previewContainer);
        }
        
        // Listen for file selection
        input.addEventListener('change', function() {
            // Clear previous previews
            previewContainer.innerHTML = '';
            
            // Check if files were selected
            if (this.files.length === 0) return;
            
            // Create heading
            const heading = document.createElement('h6');
            heading.textContent = 'Selected Files:';
            previewContainer.appendChild(heading);
            
            // Create list of files
            const fileList = document.createElement('ul');
            fileList.className = 'list-group';
            
            let totalSize = 0;
            let hasInvalidFiles = false;
            
            // Add each file to the list
            Array.from(this.files).forEach(file => {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                
                totalSize += file.size;
                
                // Check file size
                const isValidSize = file.size <= 5 * 1024 * 1024; // 5MB limit
                
                // Check file type
                const isValidType = file.type.startsWith('image/');
                
                if (!isValidSize || !isValidType) {
                    hasInvalidFiles = true;
                    listItem.classList.add('list-group-item-danger');
                }
                
                // File name and info
                listItem.innerHTML = `
                    <div>
                        <i class="fas ${file.type.startsWith('image/') ? 'fa-image' : 'fa-file'}"></i>
                        ${file.name}
                        <small class="text-muted">(${formatFileSize(file.size)})</small>
                    </div>
                    <div>
                        ${!isValidSize ? '<span class="badge bg-danger">Too large</span>' : ''}
                        ${!isValidType ? '<span class="badge bg-danger">Not an image</span>' : ''}
                    </div>
                `;
                
                fileList.appendChild(listItem);
                
                // If it's an image and valid, add preview
                if (file.type.startsWith('image/') && isValidSize) {
                    createImagePreview(file, listItem);
                }
            });
            
            previewContainer.appendChild(fileList);
            
            // Add total size
            const sizeInfo = document.createElement('div');
            sizeInfo.className = 'mt-2 text-muted';
            sizeInfo.innerHTML = `Total size: ${formatFileSize(totalSize)}`;
            previewContainer.appendChild(sizeInfo);
            
            // Add warning if there are invalid files
            if (hasInvalidFiles) {
                const warning = document.createElement('div');
                warning.className = 'alert alert-warning mt-2';
                warning.innerHTML = `
                    <strong>Warning:</strong> Some files are invalid and will not be uploaded. 
                    Please ensure all files are images and under 5MB.
                `;
                previewContainer.appendChild(warning);
            }
        });
    });
    
    // Format file size to human-readable format
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    
    // Create image preview
    function createImagePreview(file, listItem) {
        // Create a thumbnail preview
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'image-preview mt-2';
            preview.innerHTML = `<img src="${e.target.result}" alt="${file.name}" class="img-thumbnail" style="max-height: 100px;">`;
            listItem.appendChild(preview);
        };
        
        reader.readAsDataURL(file);
    }
    
    // Handle attachment deletion for existing attachments
    const deleteAttachmentBtns = document.querySelectorAll('.delete-attachment');
    deleteAttachmentBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this attachment? This cannot be undone.')) {
                const attachmentId = this.dataset.id;
                
                // Create a form to send the delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_attachment.php';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'attachment_id';
                idInput.value = attachmentId;
                
                const returnToInput = document.createElement('input');
                returnToInput.type = 'hidden';
                returnToInput.name = 'return_to';
                returnToInput.value = window.location.href;
                
                form.appendChild(idInput);
                form.appendChild(returnToInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
}); 