/**
 * BRANDS MODULE - Complete CRUD
 */

let currentEditId = null;

document.addEventListener('DOMContentLoaded', async function () {
    console.log('⭐ Brands Module Loaded');
    await loadBrands();
    setupForm();
});

async function loadBrands() {
    try {
        const container = document.querySelector('.brands-grid');
        if (!container) return;

        container.innerHTML = '<div class="col-span-3 text-center py-4">Loading...</div>';

        const brands = await window.crudOperations.getAll('brands');

        if (brands.length === 0) {
            container.innerHTML = '<div class="col-span-3 text-center py-8"><p class="text-secondary mb-4">No brands</p><button class="btn btn-primary" onclick="openModal(\'addBrandModal\')">Add Brand</button></div>';
            return;
        }

        container.innerHTML = '';
        brands.forEach(brand => {
            container.innerHTML += `
                <div class="card">
                    <div class="card-body">
                        <h3 class="text-lg font-semibold mb-2">${brand.name}</h3>
                        <p class="text-secondary text-sm mb-4">${brand.description || ''}</p>
                        <div class="flex gap-2">
                            <button class="btn btn-outline btn-sm flex-1" onclick="editBrand('${brand.id}')">Edit</button>
                            <button class="btn btn-ghost btn-sm" onclick="deleteBrand('${brand.id}')">Delete</button>
                        </div>
                    </div>
                </div>
            `;
        });

        console.log(`✅ Loaded ${brands.length} brands`);
    } catch (error) {
        console.error('❌ Error:', error);
    }
}

function setupForm() {
    const form = document.querySelector('#addBrandModal form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await saveBrand();
        });
    }
}

async function saveBrand() {
    try {
        const modal = document.getElementById('addBrandModal');
        const form = modal.querySelector('form');

        const data = {
            name: form.querySelector('[name="name"]').value.trim(),
            description: form.querySelector('[name="description"]').value.trim()
        };

        if (!data.name) throw new Error('Name is required');

        if (currentEditId) {
            await window.crudOperations.update('brands', currentEditId, data);
            showToast('Updated!', 'success');
        } else {
            await window.crudOperations.create('brands', data);
            showToast('Created!', 'success');
        }

        closeModal('addBrandModal');
        form.reset();
        currentEditId = null;
        await loadBrands();

    } catch (error) {
        console.error('❌ Error:', error);
        showToast(error.message, 'danger');
    }
}

async function editBrand(id) {
    try {
        currentEditId = id;
        const brand = await window.crudOperations.getOne('brands', id);

        const modal = document.getElementById('addBrandModal');
        const form = modal.querySelector('form');

        form.querySelector('[name="name"]').value = brand.name;
        form.querySelector('[name="description"]').value = brand.description || '';

        modal.querySelector('.modal-title').textContent = 'Edit Brand';
        openModal('addBrandModal');
    } catch (error) {
        console.error('❌ Error:', error);
    }
}

async function deleteBrand(id) {
    if (!confirm('Delete?')) return;

    try {
        await window.crudOperations.delete('brands', id);
        showToast('Deleted!', 'success');
        await loadBrands();
    } catch (error) {
        console.error('❌ Error:', error);
    }
}

console.log('✅ Brands CRUD Ready');
