// Simple Alert and Confirm System
// Just shows messages and asks yes/no questions

// Show alert message
function showAlert(message, type) {
    type = type || 'info';
    if (type === 'danger') type = 'error';
    
    var alertBox = document.createElement('div');
    alertBox.className = 'alert alert-' + type;
    alertBox.innerHTML = message + '<span class="alert-close" onclick="this.parentElement.remove()">×</span>';
    
    var container = document.getElementById('alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(alertBox);
    
    // Auto remove after 5 seconds
    setTimeout(function() {
        if (alertBox.parentElement) {
            alertBox.remove();
        }
    }, 5000);
}

// Show confirm dialog
function showConfirm(message, title) {
    // Support object parameter or string parameters
    if (typeof message === 'object') {
        var options = message;
        title = options.title || 'Confirm';
        message = options.message || '';
        var okText = options.okText || 'OK';
        var cancelText = options.cancelText || 'Cancel';
    } else {
        title = title || 'Confirm';
        var okText = 'OK';
        var cancelText = 'Cancel';
    }
    
    // Convert \n to <br> for line breaks
    message = String(message).replace(/\n/g, '<br>');
    
    // Create modal
    var modal = document.createElement('div');
    modal.className = 'modal active';
    modal.innerHTML = '<div class="modal-content">' +
        '<div class="modal-header"><h2>' + title + '</h2></div>' +
        '<div class="modal-body">' + message + '</div>' +
        '<div class="modal-actions">' +
        '<button class="btn-cancel">' + cancelText + '</button>' +
        '<button class="btn-ok">' + okText + '</button>' +
        '</div></div>';
    
    document.body.appendChild(modal);
    
    // Return promise for yes/no
    return new Promise(function(resolve) {
        modal.querySelector('.btn-ok').onclick = function() {
            modal.remove();
            resolve(true);
        };
        modal.querySelector('.btn-cancel').onclick = function() {
            modal.remove();
            resolve(false);
        };
    });
}

// confirmAsync - used by some pages
function confirmAsync(message, title, isDanger) {
    title = title || 'Confirm';
    return showConfirm(message, title);
}

// Alias for compatibility
window.showAlert = showAlert;
window.showConfirm = showConfirm;
window.confirmAsync = confirmAsync;
window.showToast = showAlert;
