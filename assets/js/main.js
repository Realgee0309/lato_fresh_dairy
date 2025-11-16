// Notification System
function showNotification(message, type = 'success') {
    const toast = document.getElementById('notification-toast');
    const icon = document.getElementById('notification-icon');
    const title = document.getElementById('notification-title');
    const messageEl = document.getElementById('notification-message');
    
    title.textContent = type.charAt(0).toUpperCase() + type.slice(1);
    messageEl.textContent = message;
    
    icon.className = `notification-icon ${type}`;
    icon.innerHTML = type === 'success' ? '<i class="fas fa-check"></i>' :
                     type === 'error' ? '<i class="fas fa-times"></i>' :
                     type === 'warning' ? '<i class="fas fa-exclamation-triangle"></i>' :
                     '<i class="fas fa-info"></i>';
    
    toast.classList.add('show');
    setTimeout(() => hideNotification(), 5000);
}

function hideNotification() {
    document.getElementById('notification-toast').classList.remove('show');
}

// Confirm delete actions
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format currency
function formatCurrency(amount) {
    return 'KES ' + parseFloat(amount).toFixed(2);
}