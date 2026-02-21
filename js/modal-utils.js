/**
 * Modal Utilities for CRUD Operations
 * Handles Add, Edit, View, and Delete modals
 */

// Open modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

// Close modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Close modal on backdrop click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal-backdrop.show');
        if (openModal) {
            openModal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
});

// Delete confirmation
function confirmDelete(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        // Call delete API here
        console.log('Deleting item:', id);
        alert('Delete functionality will be implemented soon!');
    }
}

// View item details
function viewItem(id) {
    openModal('viewModal');
    // Load item details here
    console.log('Viewing item:', id);
}

// Edit item
function editItem(id) {
    openModal('editModal');
    // Load item data into form here
    console.log('Editing item:', id);
}

// Export to Excel
function exportToExcel() {
    alert('Export to Excel functionality coming soon!');
}
