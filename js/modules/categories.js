/**
 * CATEGORIES MODULE - Complete CRUD with role-based access
 */

let currentEditId = null;

document.addEventListener('DOMContentLoaded', async function () {
    console.log('📂 Categories Module Loading...');
    await loadCategories();
});

async function loadCategories() {
    try {
        const categories = await window.crudOperations.getAll('categories');
        const grid = document.getElementById('categoriesGrid');

        if (categories.length === 0) {
            grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--text-secondary);">No categories yet. Create your first one!</p>';
            return;
        }

        grid.innerHTML = categories.map(cat => `
            <div class="card" style="padding: 24px;">
                <h4 style="margin-bottom: 8px;">${cat.name}</h4>
                <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 16px;">${cat.description || 'No description'}</p>
                ${canEdit() ? `
                    <div style="display: flex; gap: 8px;">
                        <button class="btn btn-sm btn-ghost" onclick="editCategory('${cat.id}')">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteCategory('${cat.id}')">Delete</button>
                    </div>
                ` : ''}
            </div>
        `).join('');

        console.log('✅ Loaded', categories.length, 'categories');
    } catch (error) {
        console.error('❌ Error loading categories:', error);
    }
}

function canEdit() {
    return window.currentUserRole === 'admin' || window.currentUserRole === 'super_admin';
}

window.openModal = function (modalId) {
    document.getElementById(modalId).style.display = 'flex';
    currentEditId = null;
};

window.closeModal = function (modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.getElementById('categoryForm')?.reset();
    currentEditId = null;
};

window.saveCategory = async function () {
    try {
        if (!canEdit()) {
            alert('You do not have permission to edit categories');
            return;
        }

        const form = document.getElementById('categoryForm');
        const data = {
            name: form.querySelector('[name="name"]').value.trim(),
            description: form.querySelector('[name="description"]')?.value.trim() || ''
        };

        if (!data.name) {
            throw new Error('Category name is required');
        }

        if (currentEditId) {
            await window.crudOperations.update('categories', currentEditId, data);
            showSuccess('Category updated!');
        } else {
            await window.crudOperations.create('categories', data);
            showSuccess('Category created!');
        }

        closeModal('addCategoryModal');
        await loadCategories();

    } catch (error) {
        console.error('❌ Error:', error);
        showError(error.message);
    }
};

window.editCategory = async function (id) {
    try {
        currentEditId = id;
        const category = await window.crudOperations.getOne('categories', id);

        const form = document.getElementById('categoryForm');
        form.querySelector('[name="name"]').value = category.name || '';
        if (form.querySelector('[name="description"]')) {
            form.querySelector('[name="description"]').value = category.description || '';
        }

        openModal('addCategoryModal');
    } catch (error) {
        console.error('❌ Error:', error);
    }
};

window.deleteCategory = async function (id) {
    if (!confirm('Delete this category? Products using it may be affected.')) return;

    try {
        await window.crudOperations.delete('categories', id);
        showSuccess('Category deleted!');
        await loadCategories();
    } catch (error) {
        console.error('❌ Error:', error);
        showError('Delete failed');
    }
};

function showSuccess(message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #0a0; color: white; padding: 16px 24px; border-radius: 8px; z-index: 10000;';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function showError(message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #c00; color: white; padding: 16px 24px; border-radius: 8px; z-index: 10000;';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

console.log('✅ Categories Module Ready');
