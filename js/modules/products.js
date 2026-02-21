/**
 * PRODUCTS MODULE - Complete CRUD
 */

let currentEditId = null;

document.addEventListener('DOMContentLoaded', async function () {
    console.log('📦 Products Module Loading...');

    // Check permissions
    if (window.currentUserRole === 'sales' || window.currentUserRole === 'staff') {
        // Hide create/edit/delete buttons for sales
        const createBtn = document.querySelector('.btn-primary');
        if (createBtn) createBtn.style.display = 'none';
    }

    await loadProducts();
});

async function loadProducts() {
    try {
        showLoading('productsTableBody');

        const products = await window.crudOperations.getAll('products');
        const tbody = document.getElementById('productsTableBody');

        if (products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">No products yet. Click "+ Add Product" to create one!</td></tr>';
            return;
        }

        tbody.innerHTML = products.map(product => `
            <tr>
                <td>${product.name}</td>
                <td>${product.sku || 'N/A'}</td>
                <td>${product.category || 'N/A'}</td>
                <td>${product.brand || 'N/A'}</td>
                <td>₹${product.price || 0}</td>
                <td>${product.stock || 0}</td>
                <td>
                    ${canEdit() ? `
                        <button class="btn btn-sm btn-ghost" onclick="editProduct('${product.id}')">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct('${product.id}')">Delete</button>
                    ` : '<span style="color: #888;">View Only</span>'}
                </td>
            </tr>
        `).join('');

        console.log('✅ Loaded', products.length, 'products');
    } catch (error) {
        console.error('❌ Error loading products:', error);
        showError('Failed to load products');
    }
}

function canEdit() {
    return window.currentUserRole !== 'sales' && window.currentUserRole !== 'staff';
}

function showLoading(elementId) {
    document.getElementById(elementId).innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;">Loading...</td></tr>';
}

window.openModal = function (modalId) {
    document.getElementById(modalId).style.display = 'flex';
    currentEditId = null;
};

window.closeModal = function (modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.getElementById('productForm').reset();
    currentEditId = null;
};

window.saveProduct = async function () {
    try {
        if (!canEdit()) {
            alert('You do not have permission to edit products');
            return;
        }

        const form = document.getElementById('productForm');
        const data = {
            name: form.querySelector('[name="name"]').value.trim(),
            sku: form.querySelector('[name="sku"]').value.trim(),
            category: form.querySelector('[name="category"]').value,
            brand: form.querySelector('[name="brand"]').value,
            price: parseFloat(form.querySelector('[name="price"]').value) || 0,
            stock: parseInt(form.querySelector('[name="stock"]').value) || 0,
            description: form.querySelector('[name="description"]')?.value.trim() || ''
        };

        if (!data.name || !data.sku) {
            throw new Error('Product name and SKU are required');
        }

        if (currentEditId) {
            await window.crudOperations.update('products', currentEditId, data);
            showSuccess('Product updated successfully!');
        } else {
            await window.crudOperations.create('products', data);
            showSuccess('Product created successfully!');
        }

        closeModal('addProductModal');
        await loadProducts();

    } catch (error) {
        console.error('❌ Save error:', error);
        showError(error.message);
    }
};

window.editProduct = async function (id) {
    try {
        currentEditId = id;
        const product = await window.crudOperations.getOne('products', id);

        const form = document.getElementById('productForm');
        form.querySelector('[name="name"]').value = product.name || '';
        form.querySelector('[name="sku"]').value = product.sku || '';
        form.querySelector('[name="category"]').value = product.category || '';
        form.querySelector('[name="brand"]').value = product.brand || '';
        form.querySelector('[name="price"]').value = product.price || '';
        form.querySelector('[name="stock"]').value = product.stock || '';
        if (form.querySelector('[name="description"]')) {
            form.querySelector('[name="description"]').value = product.description || '';
        }

        openModal('addProductModal');
    } catch (error) {
        console.error('❌ Edit error:', error);
        showError('Failed to load product');
    }
};

window.deleteProduct = async function (id) {
    if (!confirm('Are you sure you want to delete this product?')) return;

    try {
        await window.crudOperations.delete('products', id);
        showSuccess('Product deleted successfully!');
        await loadProducts();
    } catch (error) {
        console.error('❌ Delete error:', error);
        showError('Failed to delete product');
    }
};

function showSuccess(message) {
    const toast = document.createElement('div');
    toast.className = 'toast toast-success';
    toast.textContent = message;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #0a0; color: white; padding: 16px 24px; border-radius: 8px; z-index: 10000;';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function showError(message) {
    const toast = document.createElement('div');
    toast.className = 'toast toast-error';
    toast.textContent = message;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #c00; color: white; padding: 16px 24px; border-radius: 8px; z-index: 10000;';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

console.log('✅ Products Module Ready');
