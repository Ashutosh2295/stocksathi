/**
 * CUSTOMERS MODULE - Complete CRUD Operations
 */

let currentEditId = null;

document.addEventListener('DOMContentLoaded', async function () {
    console.log('👥 Customers Module Loaded');
    await loadCustomers();
    setupCustomerForm();
});

async function loadCustomers() {
    try {
        const tbody = document.querySelector('#customersTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading...</td></tr>';

        const customers = await window.crudOperations.getAll('customers');

        if (customers.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-secondary">No customers yet.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        customers.forEach(customer => {
            tbody.innerHTML += `
                <tr>
                    <td>${customer.name}</td>
                    <td>${customer.email || '-'}</td>
                    <td>${customer.phone || '-'}</td>
                    <td>${customer.company || '-'}</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="editCustomer('${customer.id}')">Edit</button>
                        <button class="btn btn-sm btn-ghost" onclick="deleteCustomer('${customer.id}')">Delete</button>
                    </td>
                </tr>
            `;
        });

        console.log(`✅ Loaded ${customers.length} customers`);
    } catch (error) {
        console.error('❌ Error:', error);
    }
}

function setupCustomerForm() {
    const form = document.querySelector('#addCustomerModal form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await save Customer();
        });
    }
}

async function saveCustomer() {
    try {
        const modal = document.getElementById('addCustomerModal');
        const form = modal.querySelector('form');

        const data = {
            name: form.querySelector('[name="name"]').value.trim(),
            email: form.querySelector('[name="email"]').value.trim(),
            phone: form.querySelector('[name="phone"]').value.trim(),
            company: form.querySelector('[name="company"]').value.trim(),
            address: form.querySelector('[name="address"]').value.trim(),
            status: 'active'
        };

        if (!data.name) {
            throw new Error('Name is required');
        }

        if (currentEditId) {
            await window.crudOperations.update('customers', currentEditId, data);
            showToast('Customer updated!', 'success');
        } else {
            await window.crudOperations.create('customers', data);
            showToast('Customer created!', 'success');
        }

        closeModal('addCustomerModal');
        form.reset();
        currentEditId = null;
        await loadCustomers();

    } catch (error) {
        console.error('❌ Error:', error);
        showToast(error.message, 'danger');
    }
}

async function editCustomer(id) {
    try {
        currentEditId = id;
        const customer = await window.crudOperations.getOne('customers', id);

        const modal = document.getElementById('addCustomerModal');
        const form = modal.querySelector('form');

        form.querySelector('[name="name"]').value = customer.name;
        form.querySelector('[name="email"]').value = customer.email || '';
        form.querySelector('[name="phone"]').value = customer.phone || '';
        form.querySelector('[name="company"]').value = customer.company || '';
        form.querySelector('[name="address"]').value = customer.address || '';

        modal.querySelector('.modal-title').textContent = 'Edit Customer';
        openModal('addCustomerModal');
    } catch (error) {
        console.error('❌ Error:', error);
        showToast('Failed to load', 'danger');
    }
}

async function deleteCustomer(id) {
    if (!confirm('Delete this customer?')) return;

    try {
        await window.crudOperations.delete('customers', id);
        showToast('Customer deleted!', 'success');
        await loadCustomers();
    } catch (error) {
        console.error('❌ Error:', error);
        showToast('Failed to delete', 'danger');
    }
}

console.log('✅ Customers CRUD Ready');
