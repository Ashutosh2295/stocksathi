/**
 * PROMOTIONS MODULE - Complete Working CRUD
 * Fully functional promotion management with Firestore
 */

let currentEditId = null;

// Initialize module
document.addEventListener('DOMContentLoaded', async function () {
    console.log('🎁 Promotions Module Initializing...');
    await loadPromotions();
    setupEventListeners();
});

// Load all promotions
async function loadPromotions() {
    try {
        const container = document.querySelector('.grid.grid-cols-3');
        if (!container) return;

        container.innerHTML = '<div class="col-span-3 text-center py-12"><div class="animate-spin inline-block w-12 h-12 border-4 border-primary border-t-transparent rounded-full"></div><p class="mt-4 text-secondary">Loading promotions...</p></div>';

        const promotions = await window.crudOperations.getAll('promotions');

        if (promotions.length === 0) {
            container.innerHTML = '<div class="col-span-3 text-center py-12"><div style="font-size: 4rem; margin-bottom: 16px;">🎁</div><h3 class="text-lg font-semibold mb-2">No promotions yet</h3><p class="text-secondary mb-4">Create your first promotion</p><button class="btn btn-primary" onclick="openModal(\'addPromotionModal\')">+ Create Promotion</button></div>';
            return;
        }

        container.innerHTML = '';
        promotions.forEach(promo => {
            container.innerHTML += createPromotionCard(promo);
        });

        console.log(`✅ Loaded ${promotions.length} promotions`);
    } catch (error) {
        console.error('❌ Error:', error);
        document.querySelector('.grid.grid-cols-3').innerHTML = '<div class="col-span-3 text-center py-12 text-danger">Failed to load. Check console.</div>';
    }
}

// Create card HTML
function createPromotionCard(promo) {
    return `
        <div class="card">
            <div class="card-body">
                <div class="flex justify-between items-start mb-4">
                    <div style="font-size: 3rem;">🎁</div>
                    <span class="badge badge-success">Active</span>
                </div>
                <h3 class="text-lg font-semibold mb-2">${promo.name}</h3>
                <p class="text-secondary text-sm mb-4">${promo.description || ''}</p>
                <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border-light);">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-secondary">Discount:</span>
                        <span class="font-semibold text-primary">${promo.discountValue}% OFF</span>
                    </div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-secondary">Code:</span>
                        <code>${promo.code}</code>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-outline btn-sm flex-1" onclick="editPromotion('${promo.id}')">✏️ Edit</button>
                    <button class="btn btn-ghost btn-sm" onclick="deletePromotion('${promo.id}')">🗑️</button>
                </div>
            </div>
        </div>
    `;
}

// Setup listeners
function setupEventListeners() {
    const form = document.querySelector('#addPromotionModal form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await savePromotion();
        });
    }
}

// Save promotion
async function savePromotion() {
    try {
        const modal = document.getElementById('addPromotionModal');
        const form = modal.querySelector('form');

        const data = {
            name: form.querySelector('input[placeholder="Enter promotion name"]').value.trim(),
            description: form.querySelector('textarea').value.trim(),
            discountType: 'percentage_off',
            discountValue: parseFloat(form.querySelectorAll('input[type="number"]')[0].value),
            code: form.querySelectorAll('input[type="text"]')[1].value.toUpperCase(),
            startDate: form.querySelectorAll('input[type="date"]')[0].value,
            endDate: form.querySelectorAll('input[type="date"]')[1].value,
            status: 'active'
        };

        if (!data.name || !data.code || !data.discountValue) {
            throw new Error('Please fill all required fields');
        }

        if (currentEditId) {
            await window.crudOperations.update('promotions', currentEditId, data);
            showToast('Promotion updated!', 'success');
        } else {
            await window.crudOperations.create('promotions', data);
            showToast('Promotion created!', 'success');
        }

        closeModal('addPromotionModal');
        form.reset();
        currentEditId = null;
        await loadPromotions();

    } catch (error) {
        console.error('❌ Error:', error);
        showToast(error.message, 'danger');
    }
}

// Edit promotion
async function editPromotion(id) {
    try {
        currentEditId = id;
        const promo = await window.crudOperations.getOne('promotions', id);

        const modal = document.getElementById('addPromotionModal');
        const form = modal.querySelector('form');

        form.querySelector('input[placeholder="Enter promotion name"]').value = promo.name;
        form.querySelector('textarea').value = promo.description || '';
        form.querySelectorAll('input[type="number"]')[0].value = promo.discountValue;
        form.querySelectorAll('input[type="text"]')[1].value = promo.code;
        form.querySelectorAll('input[type="date"]')[0].value = promo.startDate || '';
        form.querySelectorAll('input[type="date"]')[1].value = promo.endDate || '';

        modal.querySelector('.modal-title').textContent = 'Edit Promotion';
        openModal('addPromotionModal');
    } catch (error) {
        console.error('❌ Error:', error);
        showToast('Failed to load', 'danger');
    }
}

// Delete promotion
async function deletePromotion(id) {
    if (!confirm('Delete this promotion?')) return;

    try {
        await window.crudOperations.delete('promotions', id);
        showToast('Deleted!', 'success');
        await loadPromotions();
    } catch (error) {
        console.error('❌ Error:', error);
        showToast('Failed to delete', 'danger');
    }
}

console.log('✅ Promotions CRUD Ready');
