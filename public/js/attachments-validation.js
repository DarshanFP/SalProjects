/**
 * Shared attachment validation functions
 * Used across multiple attachment upload forms
 */

/**
 * Format file size in human-readable format
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Validate file type
 */
function validateFileType(file, allowedTypes) {
    return allowedTypes.includes(file.type);
}

/**
 * Validate file size
 */
function validateFileSize(file, maxSize) {
    return file.size <= maxSize;
}

/**
 * Show file error message
 */
function showFileError(container, message) {
    // Remove any existing error
    const existingError = container.querySelector('.file-error');
    if (existingError) {
        existingError.remove();
    }
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger mt-2 file-error';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
    container.appendChild(errorDiv);
}

/**
 * Show file success message
 */
function showFileSuccess(container, fileName, fileSize) {
    // Remove any existing success message
    const existingSuccess = container.querySelector('.file-success');
    if (existingSuccess) {
        existingSuccess.remove();
    }
    
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success mt-2 file-success';
    successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + fileName + 
                         ' (' + formatFileSize(fileSize) + ')';
    container.appendChild(successDiv);
}

/**
 * Validate IES/IIES file upload
 */
function validateIESFile(input, fieldName) {
    const file = input.files[0];
    const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    const maxSize = 7340032; // 7MB (server allows up to 7MB, but we display 5MB to users)
    const displayMaxSize = 5242880; // 5MB - for display purposes only
    
    // Get container for error messages
    const container = input.closest('.form-group') || input.parentElement;
    
    // Remove any previous messages
    const existingError = container.querySelector('.file-error');
    const existingSuccess = container.querySelector('.file-success');
    if (existingError) existingError.remove();
    if (existingSuccess) existingSuccess.remove();
    
    if (file) {
        // Check file type
        if (!validateFileType(file, validTypes)) {
            showFileError(container, 'Invalid file type. Only PDF, JPG, and PNG files are allowed.');
            input.value = '';
            return false;
        }
        
        // Check file size
        if (!validateFileSize(file, maxSize)) {
            showFileError(container, 'File size must not exceed 5 MB.');
            input.value = '';
            return false;
        }
        
        // Show success indicator
        showFileSuccess(container, file.name, file.size);
    }
    return true;
}
